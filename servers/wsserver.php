#!/opt/local/bin/php -q
<?php
/**
 * PHP WebSocket
 * A lightweight implementation
 * of the WebSocket protocol
 *
 * @created   2014-01-04
 * @updated   2014-06-10
 * 
 * @author    Greg <greg@deback.fr>
 * @filename  wsserver.php
 * @copyright GPLv3
 * @version   1.0
 * 
 * @see       IETF - RFC 6455
 *            http://tools.ietf.org/html/rfc6455
 * @see       Mozilla Developer Network - WebSocket
 *            https://developer.mozilla.org/en-US/docs/WebSockets/Writing_WebSocket_server
 *
 * @usage     php -q wsserver.php (or) ./wsserver.php
 */


include 'server.php';
\set_time_limit(0);
\error_reporting(E_ALL);
\date_default_timezone_set('Europe/Paris');


/**
 * @class WebSocketServer
 * @brief A lightweight PHP server
 */
class WebSocketServer extends SocketServer {


  /**
   * Processors
   */
  private $procs = array();


  /**
   * Open new connection
   *
   * @return bool  True if accepted
   */
  protected function opening($client) {

    $head = array();
    $hand = false;
    while ($line = \trim(\fgets($client, 1024))) {
      if (\preg_match('/^([a-zA-Z\-]+): (.+)$/', $line, $match)) {
        $head[$match[1]] = \trim($match[2]);
      } else if (\preg_match('/^GET /', $line)) {
        $hand = true;
      }
    }
    if ($hand) {
      if (isset($head['Sec-WebSocket-Key'])) {
        $key  = $head['Sec-WebSocket-Key'];
        $data = $this->handshake($key);
        \fwrite($client, $data, \strlen($data));
      } else {
        $this->log('Missing key');
        \fwrite($client, 'HTTP/1.1 400 Bad Request' . "\r\n\r\n");
        \fclose($client);
        return false;
      }
    }
    return true;

  }


  /**
   * Handshaking
   *
   * @param  string $key  WebSocket key
   * @return string       Handshake response
   */
  private function handshake($key) {

    $this->log('Handshake: %s', $key);
    $key .= '258EAFA5-E914-47DA-95CA-C5AB0DC85B11';
    $hash = \base64_encode(\sha1($key, true));
    $data = 'HTTP/1.1 101 Switching Protocols' . "\r\n" .
      'Connection: Upgrade' . "\r\n" .
      'Upgrade: websocket' . "\r\n" .
      'Sec-WebSocket-Accept: ' . $hash . "\r\n\r\n";
    $this->log('Accept: %s', $hash);
    return $data;

  }


  /**
   * Register processor
   *
   * @param  object $proc  Processor
   */
  public function register($proc) {

    $this->procs[] = $proc;
    $proc->bind($this);
    $this->log('Registered %s', \get_class($proc));
    return true;

  }


  /**
   * Unregister processor
   *
   * @param  object $proc  Processor
   */
  public function unregister($proc) {

    $i = \array_search($proc, $this->procs, true);
    if ($i === false) {
      return false;
    }
    $proc->unbind($this);
    \array_splice($this->procs, $i, 1);
    $this->log('Unregistered %s', \gettype($proc));
    return true;

  }


  /**
   * Read data
   *
   * @param  resource $client  Client socket
   * @param  array    $opts    Read options
   * @return string            Incoming data
   */
  protected function read($client, $opts = array()) {

    $head = \ord(\fgets($client, 2));
    if (!$head) return false;

    $fin  = $head & 128;
    $code = $head % 16;

    $len  = \ord(\fgets($client, 2));
    $mask = $len & 128;
    if ($mask) $len -= 128;

    if ($len > 125) {
      $next = ($len == 126) ? 2 : 8;
      $data = \fgets($client, $next + 1);
      $arr  = \unpack('n*', $data);
      $len  = 0;
      foreach ($arr as $n) {
        $len = $len * 256 + $n;
      }
    }

    if ($mask) {
      $data = \fgets($client, 5);
      if (\strlen($data) == 4) {
        $key  = \unpack('C4', $data);
      } else {
        $this->log('Invalid masked data: ' . \bin2hex($data));
        return false;
      }
    }

    $data = '';
    while (\strlen($data) < $len)
      $data .= \fgets($client, $len - \strlen($data) + 1);
    $feed = \unpack('C*', $data);

    if (\count($feed) != $len) {
      $this->log('Incorrect data length');
      return false;
    }

    if ($mask) {
      $msg = '';
      foreach ($feed as $i=>$byte)
        $msg .= \chr($byte ^ $key[1 + (($i - 1) % 4)]);
    } else {
      $this->log('No mask for this');
      $msg = '';
      foreach ($feed as $i=>$byte)
        $msg .= \chr($byte);
    }

    switch ($code) {
    case 0: // partial frame
      break;
    case 8: // bye
      $this->close($client);
      break;
    case 9: // ping
      $pong = array(10, $msg);
      $this->write($client, $pong);
      break;
    case 10: // pong
      break;
    }

    return array($code, $msg);

  }


  /**
   * Write data
   *
   * @param  resource $client  Client socket
   * @param  string   $data    Response data
   * @param  array    $opts    Read options
   * @return bool              True on success
   */
  public function write($client, &$data, $opts = array()) {

    if (!$client) {
      foreach ($this->clients as $client)
        if ($client !== $this->master)
          $this->write($client, $data, $opts);
      return;
    }

    $opts += array('mask' => false);
    list($code, $msg) = $data;
    $mask = $opts['mask'];

    // code: un(masked)
    // text:    1 (129)
    // binary:  2 (130)
    // close:   8 (136)
    // ping:    9 (137)
    // pong:   10 (138)
    $mbit  = $mask ? 128 : 0;
    $frame = \chr(128 + $code);
    $len   = \strlen($msg);
    if ($len < 126) {
      $frame .= \chr($mbit + $len);
    } else {
      $data = \pack('n*', $len);
      $two  = ($len < 65536);
      $frame .= \chr($mbit + ($two ? 126 : 127));
      $frame .= \str_pad($data, $two ? 2 : 8, \chr(0), STR_PAD_LEFT);
    }

    if ($mask) {
      $key = array();
      for ($i = 0; $i < 4; $i++) {
        $key[$i] = \rand(0, 255);
        $frame .= \chr($key[$i]);
      }
      for ($i = 0; $i < \strlen($msg); $i++) {
        $frame .= \chr(\ord($msg{$i}) ^ $key[$i % 4]);
      }
    } else {
      $frame .= $msg;
    }

    \fwrite($client, $frame, \strlen($frame));

  }


  /**
   * Process data
   *
   * @param  resource $client  Client socket
   * @param  string   $data    Request data
   * @return bool              True to keep alive
   */
  protected function process($client, &$data) {

    list($code, $msg) = $data;
    if (\strlen($msg) === 0) {
      $this->log('End of transmission?');
      return false;
    }

    $pass = true;
    foreach ($this->procs as $proc)
      $pass &= $proc->process($this, $client, $data);
    return $pass;

  }


}


/**
 * @interface SocketProcessor
 * @brief     Socket processor interface
 */
interface SocketProcessor {


  /**
   * Bind processor to server
   *
   * @param  resource $server  Server
   */
  public function bind($server);


  /**
   * Unbind processor from server
   *
   * @param  resource $server  Server
   */
  public function unbind($server);


  /**
   * Process data
   *
   * @param  resource $client  Client socket
   * @param  string   $data    Request data
   * @return bool              True to keep alive
   */
  public function process($server, $client, &$data);


}


/**
 * @class TestProcessor
 * @brief A simple test processor
 */
class TestProcessor implements SocketProcessor {


  /**
   * @copydoc
   */
  public function bind($server) {

  }


  /**
   * @copydoc
   */
  public function unbind($server) {

  }


  /**
   * @copydoc
   */
  public function process($server, $client, &$data) {

    list($code, $msg) = $data;

    $pass = true;
    switch ($code) {

    case 1:
      switch ($msg) {
      case '/whoami':
        $data = array(1, 'chat:"You are @Guest' . \intval($client) . '"');
        $server->write($client, $data);
        break;
      case '/hello':
        $data = array(1, 'chat:"Hello human!"');
        $server->write($client, $data);
        break;
      case '/icon':
        $path = \dirname(__FILE__) . '/../assets/icon.png';
        $data = array(1, 'file:{"name":"icon.png","type":"image/png","size":0}');
        $server->write($client, $data);
        $data = array(2, \file_get_contents($path));
        $server->write($client, $data);
        break;
      case '/bye':
        $data = array(1, 'chat:"Bye human!"');
        $server->write($client, $data);
        $pass = false;
        break;
      default:
        // $data = array(1, '@Guest' . \intval($client) . ': ' . $msg);
        // $data = array(1, $msg); //'@Guest' . \intval($client) . ': ' . $msg);
        foreach ($server->clients as $i=>$other)
          if ($i && $other !== $client)
            $server->write($other, $data);
        break;
      }
      break;

    case 2:
      // broadcast
      foreach ($server->clients as $i=>$other)
        if ($i && $other !== $client)
          $server->write($other, $data);
      break;

    }

    return $pass;

  }


}


//$server = new WebSocketServer('172.18.78.103', 12345);
//$server = new WebSocketServer('192.168.1.61', 12345);
$server = new WebSocketServer('localhost', 12345);
$test   = new TestProcessor();
$server->register($test);
$server->listen();
