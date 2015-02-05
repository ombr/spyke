<?php
/**
 * PHP Socket server
 * A basic PHP server
 *
 * @created   2014-01-04
 * @updated   2014-06-10
 * 
 * @author    Greg <greg@deback.fr>
 * @filename  server.php
 * @copyright GPLv3
 * @version   1.0
 */


declare(ticks=1);


/**
 * @class SocketServer
 */
abstract class SocketServer {


  /**
   * Config & sockets
   */
  private $last;
  private $host;
  protected $master;
  public $clients;


  /**
   * Constructor
   *
   * @param  string $host  Hostname or IP
   * @param  int    $port  Port number
   */
  public function __construct($host, $port) {

    $this->last    = \time();
    $this->host    = $host . ':' . $port;
    $this->master  = false;
    $this->clients = array();

    \pcntl_signal(SIGTERM, array($this, 'signal'));
    \pcntl_signal(SIGINT,  array($this, 'signal'));

  }

  /**
   * Destructor
   */
  public function __destruct() {

    $this->quit();

  }


  /**
   * Signal handler
   *
   * @param  int $signal  Signal code
   */
  public function signal($signal) {

    $this->log('Killed with signal: %d', $signal);
    exit();

  }


  /**
   * Log
   */
  protected function log() {

    $msg = \call_user_func_array('\\sprintf', \func_get_args());
    echo $msg . PHP_EOL;

  }


  /**
   * Enable server
   */
  public function listen() {

    $main = @\stream_socket_server('tcp://' . $this->host, $no, $desc);
    if (!$main) {
      $this->log('Err. %d: %s', $no, $desc);
      exit(-1);
    }

    $this->master    = $main;
    $this->clients[] = $main;
    $this->log('Listening on %s', $this->host);
    $this->main();
    $this->log('No activity, quitting');
    exit();

  }


  /**
   * Disable server
   */
  private function quit() {

    @\fclose($this->master);
    while ($client = \array_shift($this->clients)) {
      @\fclose($client);
    }
    $this->master = false;
    $this->log('Server stopped!');

  }


  /**
   * Open new connection
   *
   * @return bool  True if accepted
   */
  protected function open() {

    $client = \stream_socket_accept($this->master, 5);

    if (\method_exists($this, 'opening'))
      if (!$this->opening($client)) return false;

    // $i = 0;
    // while (isset($this->clients[$i])) $i++;
    // $this->clients[$i] = $client;
    $this->clients[] = $client;
    $this->log('New socket #%d', \count($this->clients));
    return true;

  }


  /**
   * Closing connection
   *
   * @param  resource $client  Client socket
   * @return bool              True if closed
   */
  protected function close($client) {

    $i = \array_search($client, $this->clients, true);
    if ($i === false) {
      $this->log('No socket: %d', $i);
      return false;
    }

    if (\method_exists($this, 'closing'))
      if (!$this->closing($client)) return false;

    @\fclose($client);
    \array_splice($this->clients, $i, 1);
    // unset($this->clients[$i]);
    $this->log('Socket closed', $i);
    return true;

  }


  /**
   * Main loop
   */
  private function main() {

    // while (\count($this->clients) > 1 || \time() - $this->last < 10) {
    while (true) {
      $read  = $this->clients;
      $write = $except = null;
      $n = @\stream_select($read, $write, $except, 5);
      if ($n === false) break;
      foreach ($read as $client) {
        if ($client === $this->master) {
          $this->open();
          continue;
        }
        $data = $this->read($client);
        if ($data === false) continue;
        $this->last = \time();
        if (!$this->process($client, $data))
          $this->close($client);
      }
    }

  }


  /**
   * Read data
   *
   * @param  resource $client  Client socket
   * @param  string            Incoming data
   */
  abstract protected function read($client, $opts = array());


  /**
   * Write data
   *
   * @param  resource $client  Client socket
   * @param  string   $data    Response data
   * @param  integer  $code    Frame type code
   */
  abstract public function write($client, &$data, $opts = array());


  /**
   * Process data
   *
   * @param  resource $client  Client socket
   * @param  string   $data    Request data
   * @return bool              True to keep alive
   */
  abstract protected function process($client, &$data);


}
