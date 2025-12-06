<?php
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Ratchet\App;

require dirname(__DIR__) . '/vendor/autoload.php';

class GameWebSocket implements MessageComponentInterface
{
    private array $clients = [];

    public function onOpen(ConnectionInterface $conn)
    {
        $this->clients[$conn->resourceId] = $conn;
        echo "[OPEN] Client #{$conn->resourceId}\n";
    }

    public function onMessage(ConnectionInterface $from, $msg)
    {
        $data = json_decode($msg, true);

        foreach ($this->clients as $client) {
            $client->send(json_encode([
                'type' => $data['type'],
                'payload' => $data['payload']
            ]));
        }

        echo "[MESSAGE] From {$from->resourceId}: {$msg}\n";
    }

    public function onClose(ConnectionInterface $conn)
    {
        unset($this->clients[$conn->resourceId]);
        echo "[CLOSE] Client #{$conn->resourceId}\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        echo "[ERROR] {$e->getMessage()}\n";
        $conn->close();
    }
}

$server = new App('localhost', 8080);
$server->route('/game', new GameWebSocket(), ['*']);
echo "WebSocket server running on ws://localhost:8080/game\n";
$server->run();


