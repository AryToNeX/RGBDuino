<?php

namespace AryToNeX\RGBDuino\client;

class TCPClientManager{

	/** @var Status */
	protected $status;
	/** @var resource */
	protected $sock;
	/** @var int */
	protected $port;

	public function __construct(Status $status, int $port){
		$this->status = $status;
		$this->sock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
		$this->port = $port;
		socket_set_nonblock($this->sock);
		socket_set_option($this->sock, SOL_SOCKET, SO_REUSEADDR, 1);
		socket_bind($this->sock, "0.0.0.0", $this->port);
		socket_listen($this->sock);
		echo "TCP Client listening on port " . $this->port . "\n";
	}

	public function listenToClientCommands() : void{
		$accept = socket_accept($this->sock);
		if(is_resource($accept)){
			socket_getpeername($accept, $ip, $port);
			echo "TCP: Connection from $ip:$port. Serving...\n";
			$str = self::socket_read_until($accept, "\n");
			$str = explode(" ", $str);
			switch(array_shift($str)){
				case "ping":
					socket_write($accept, "PONG\n");
					echo "TCP: Received internal ping\n";
					break;
				default:
					socket_write($accept, "UNDEFINED_COMMAND\n");
					echo "TCP: Undefined command\n";
					break;
			}
			if(implode(" ", $str) !== "") socket_shutdown($accept, 2);
			socket_close($accept);

			echo "TCP: Connection from $ip:$port closed.\n";
		}
	}

	public function closeClient() : void{
		$linger = array('l_linger' => 0, 'l_onoff' => 1);
		socket_set_option($this->sock, SOL_SOCKET, SO_LINGER, $linger);
		socket_shutdown($this->sock);
		socket_close($this->sock);
	}

	/**
	 * @param        $sock
	 * @param string $str
	 * @param bool   $or_until_data_finish
	 * @param int    $timeout
	 *
	 * @return string
	 */
	protected static function socket_read_until(
		$sock,
		string $str,
		bool $or_until_data_finish = true,
		int $timeout = 5
	) : string{
		$data = "";
		$buf = "";
		$preTime = time();
		while(true){
			$by = socket_recv($sock, $buf, 1, MSG_DONTWAIT);
			// if char reached break
			if($buf === $str) break;

			// if remote disconnects break
			if($by === 0) break;

			// if connection timeouts break
			if(time() - $preTime > $timeout) break;

			// if data finishes break
			if($or_until_data_finish && $buf === null){
				if($data !== "") break;
			}

			// add buffer to data string
			$data .= $buf;
		}

		return $data;
	}

}