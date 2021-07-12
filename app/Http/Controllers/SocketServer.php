<?php

namespace App\Http\Controllers;

use App\Classes\Chess;
use App\Models\Game;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SocketServer {

    protected string $address = '127.0.0.1';
    protected int $port = 8090;
    protected string $currentGame = Chess::class;

    public function sendHeaders($headersText, $socket, $host, $port) {
        $headers = array();
        $tmpLine = preg_split("/\r\n/", $headersText);

        foreach($tmpLine as $line) {
            $line = rtrim($line);
            if (preg_match('/\A(\S+): (.*)\z/', $line, $matches)) {
                $headers[$matches[1]] = $matches[2];
            }
        }
        $key = $headers['Sec-WebSocket-Key'];
        $sKey = base64_encode(pack('H*', sha1($key . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11')));

        $strHeadr = "HTTP/1.1 101 Switching Protocols \r\n" .
            "Upgrade: websocket \r\n" .
            "Connection: Upgrade \r\n" .
            "WebSocket-Origin: $host \r\n" .
            "WebSocket-Location: ws://$host:$port/socket_server.php \r\n" .
            "Sec-WebSocket-Accept: $sKey \r\n\r\n";

        socket_write($socket, $strHeadr, strlen($strHeadr));
    }

    public function newConnectionACK() {
        $game = Game::latest()->first();
        $messageArray = [
            'id' => $game->id,
            'type' => $game->type
        ];
        $ask = $this->seal(json_encode($messageArray));

        return $ask;
    }

    public function seal($socketData) {
        $b1 = 0x81;
        $length = strlen($socketData);
        $header = "";

        if ($length <= 125) {
            $header = pack('CC', $b1, $length);
        } else if ($length >125 && $length < 65536) {
            $header = pack('CCn', $b1, 126, $length);
        } else {
            $header = pack('CCNN', $b1, 127, $length);
        }

        return $header . $socketData;
    }

    public function unseal($socketData) {
        $length = ord($socketData[1]) & 127;

        if ($length == 126) {
            $mask = substr($socketData, 4, 4);
            $data = substr($socketData, 8);
        } else if ($length == 127) {
            $mask = substr($socketData, 10, 4);
            $data = substr($socketData, 14);
        } else {
            $mask = substr($socketData, 2, 4);
            $data = substr($socketData, 6);
        }

        $socketStr = "";
        for($i=0;$i<strlen($data);++$i) {
            $socketStr .= $data[$i] ^ $mask[$i%4];
        }

        return $socketStr;
    }

    public function send($message, $clientSocketArray) {
        foreach($clientSocketArray as $clientSocket) {
            @socket_getpeername($clientSocket, $clientIpAddress, $clientPort);
            if ($message->receivers != [] && array_search([
                'address' => $clientIpAddress,
                'port' => $clientPort
            ], $message->receivers) === false)
                continue;
            @socket_write($clientSocket, $message->message, strlen($message->message));
        }

        return true;
    }

    public function response($message, $clientIpAddress, $clientPort) {
        $response = null;
        $receivers = [];
        if ($message === null)
            return null;
        $game = new ($this->currentGame)();
        if ($message->user_id ?? null) {
            $user = User::find($message->user_id);
            Auth::login($user);
        }
        switch($message->type) {
            case 'init': {
                DB::table('game_user')->where('user_id', $message->user_id)->where('game_id', $message->game_id)->update(['ip'=>$clientIpAddress . ':' . $clientPort]);
                $data = $game->getGameObjects($message->game_id);
                $response = [
                    'type' => 'init',
                    'data' => [
                        'pieces' => $data,
                        'game' => $message->game_id
                    ]
                ];
                $receivers[] = [
                    'address' => $clientIpAddress,
                    'port' => $clientPort
                ];
                break;
            }
            case 'get_moves': {
                $response = [
                    'type' => 'get_moves',
                    'data' => [
                        'pieces' => $game->getMoves($message->id, []),
                        'game' => $message->game_id
                    ]
                ];
                $receivers[] = [
                    'address' => $clientIpAddress,
                    'port' => $clientPort
                ];
                break;
            }
            case 'move': {
                $game->updateObject($message->id, ['posX' => $message->x, 'posY' => $message->y]);
                $data = $game->getGameObjects($message->game_id);
                $response = [
                    'type' => 'update',
                    'data' => [
                        'game' => $message->game_id,
                        'pieces' => $data
                    ]
                ];
                $players = DB::table('game_user')->where('game_id', $message->game_id)->whereNotNull('ip')->get();
                foreach($players as $player)
                    $receivers[] = [
                        'address' => Str::before($player->ip, ':'),
                        'port' => Str::after($player->ip, ':')
                    ];
                break;
            }
        }

        return (object)[
            'receivers' =>  $receivers,
            'message' => $this->seal(json_encode($response))
        ];
    }

    public function init() {

        if (($sock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP)) === false) {
            echo "Не удалось выполнить socket_create(): причина: " . socket_strerror(socket_last_error()) . "\n";
        }

        socket_set_option($sock, SOL_SOCKET, SO_REUSEADDR, 1);

        if (socket_bind($sock, $this->address, $this->port) === false) {
            echo "Не удалось выполнить socket_bind(): причина: " . socket_strerror(socket_last_error($sock)) . "\n";
        }

        if (socket_listen($sock, 5) === false) {
            echo "Не удалось выполнить socket_listen(): причина: " . socket_strerror(socket_last_error($sock)) . "\n";
        }

        $clientSocketArray = array($sock);

        do {
            $newSocketArray = $clientSocketArray;
            $nullA = [];
            socket_select($newSocketArray, $nullA, $nullA, 0, 10);

            if (in_array($sock, $newSocketArray)) {
                if (($newSocket = socket_accept($sock)) === false) {
                    echo "Не удалось выполнить socket_accept(): причина: " . socket_strerror(socket_last_error($sock)) . "\n";
                    break;
                }

                $clientSocketArray[] = $newSocket;

                $header = socket_read($newSocket, 1024);
                $this->sendHeaders($header, $newSocket, $this->address, $this->port);

                $newSocketArrayIndex = array_search($sock, $newSocketArray);
                unset($newSocketArray[$newSocketArrayIndex]);
            }

            foreach ($newSocketArray as $newSocketArrayResource) {
                while(@socket_recv($newSocketArrayResource, $socketData, 1024, 0) >= 1) {
                    $socketMessage = $this->unseal($socketData);
                    echo $socketMessage . "\n";
                    $messageObj = json_decode($socketMessage);

                    socket_getpeername($newSocketArrayResource, $clientIpAddress, $clientPort);

                    $message = $this->response($messageObj, $clientIpAddress, $clientPort);

                    if ($message !== null)
                        $this->send($message, $clientSocketArray);

                    break 2;
                }
            }

        } while (true);

        socket_close($sock);
    }
}
