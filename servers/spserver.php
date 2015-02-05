#!/opt/local/bin/php -q
<?php
/**
 * Simple Flash Socket Policy Server
 * A lightweight implementation
 * of the WebSocket protocol
 *
 * @created   2014-06-10
 * @updated   2014-06-10
 * 
 * @author    Greg <greg@deback.fr>
 * @filename  spserver.php
 * @copyright GPLv3
 * @version   1.0
 * 
 * @see       Gimite / web-socket-js (New BSD) & SWFObject (MIT)
 *            https://github.com/gimite/web-socket-js
 *            Copyright (C) 2014 Hiroshi Ichikawa
 * @see       Simple Flash Socket Policy Server (GPL)
 *            http://www.lightsphere.com/dev/articles/flash_socket_policy.html
 *            Copyright (C) 2008 Jacqueline Kira Hamilton
 */


include 'server.php';
\set_time_limit(0);
\error_reporting(E_ALL);
\date_default_timezone_set('Europe/Paris');


/**
 * @class SocketPolicy
 * @brief A simple Flash Socket Policy server
 */
class SocketPolicy extends SocketServer {


  /**
   * Read data
   *
   * @param  resource $client  Client socket
   * @param  array    $opts    Read options
   * @return string            Incoming data
   */
  protected function read($client, $opts = array()) {

    return \fread($client, 1024);

  }


  /**
   * Write data
   *
   * @param  resource $client  Client socket
   * @param  string   $msg     Response message
   * @param  array    $opts    Read options
   * @return bool              True on success
   */
  public function write($client, &$data, $opts = array()) {

    return \fwrite($client, $data, \strlen($data));

  }


  /**
   * Process data
   *
   * @param  resource $client  Client socket
   * @param  string   $data    Request data
   * @return bool              True to keep alive
   */
  protected function process($client, &$data) {

    if (strlen($data) === 0) {
      $this->log('End of transmission');
      return false;
    }
    if (preg_match('/.*policy\-file.*/i', $data)) {
      echo 'Sending policy: ' . $data . PHP_EOL;
      $xml = '<cross-domain-policy>' .
        '<allow-access-from domain="*" to-ports="*" />' .
        '</cross-domain-policy>';
      $this->write($client, $xml);
      return false;
    }
    return true;

  }


}


$policy = new SocketPolicy('127.0.0.1', 843);
$policy->listen();