<?php
require_once(__DIR__ . '/../config.php');
class Telegram {
	private $TOKEN = '';
	private $curl = false;

	public function __construct() {
		$this->TOKEN = BOT_TOKEN;

		$this->curl = curl_init();
		curl_setopt_array($this->curl, [
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_POST => true,
			CURLOPT_HTTPHEADER => [
				'Content-Type: application/json; charset=utf-8'
			]
		]);
	}

	public function getTelegram(string $method, array $query = []): array {
		$json = json_encode($query);

		$url = "https://api.telegram.org/bot{$this->TOKEN}/{$method}";

		curl_setopt_array($this->curl, [
			CURLOPT_URL => $url,
			CURLOPT_POSTFIELDS => $json."\n",
		]);
		$data = curl_exec($this->curl);

		$result = json_decode($data, true);
		if (is_null($result))
			$result = $data;

		file_put_contents("/temp/tg-log/" . date("y-m-d") . "-getTelegram-xNCTU", $method . json_encode($query, JSON_PRETTY_PRINT) . "\n" . json_encode($result, JSON_PRETTY_PRINT) . "\n\n\n", FILE_APPEND);

		return $result;
	}

	public function sendMsg(array $query): array {
		return $this->getTelegram('sendMessage', $query);
	}

	public function sendPhoto(array $query): array {
		return $this->getTelegram('sendPhoto', $query);
	}

	public function editMarkup(array $query): array {
		return $this->getTelegram('editMessageReplyMarkup', $query);
	}

	public function deleteMsg(int $chat, int $msg): array {
		return $this->getTelegram('deleteMessage', [
			'chat_id' => $chat,
			'message_id' => $msg,
		]);
	}
}
