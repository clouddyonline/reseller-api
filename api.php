<?php
namespace ClouDDy\Reseller;
class Api
{
	private const API_URL = "https://console.clouddy.online/reseller/api";
	private $token;
	public function __construct($token)
	{
		$this->token = $token;
	}
	public function getInfo()
	{
		return $this->sendRequest("info");
	}
	public function editProfile($fields)
	{
		return $this->sendRequest("profile/edit", $fields);
	}
	public function getUsers($page = 1, $filters = array(), $by = "id", $order = "ASC")
	{
		return $this->getItems("users", $page, $filters, $by, $order);
	}
	public function getUser($id)
	{
		$users = $this->getUsers(1, array("id" => $id));
		return $users["items"][0] ?? null;
	}
	public function addUser($fields)
	{
		return $this->sendRequest("users/add", $fields);
	}
	public function editUser($user, $fields)
	{
		return $this->sendRequest("users/edit/".$user, $fields);
	}
	public function deleteUser($user)
	{
		return $this->sendRequest("users/delete/".$user);
	}
	public function getUserTariffs($user)
	{
		return $this->sendRequest("users/tariffs/".$user);
	}
	public function renewUser($user)
	{
		return $this->sendRequest("users/renew/".$user);
	}
	public function cancelUser($user)
	{
		return $this->sendRequest("users/cancel/".$user);
	}
	public function activateUser($user, $tariff)
	{
		return $this->sendRequest("users/activate/".$user."/".$tariff);
	}
	public function refillUser($user, $tariff, $fields)
	{
		return $this->sendRequest("users/refill/".$user."/".$tariff, $fields);
	}
	public function getDevices($page = 1, $filters = array(), $by = "last", $order = "DESC")
	{
		return $this->getItems("users/devices", $page, $filters, $by, $order);
	}
	public function editDevice($device, $fields)
	{
		return $this->sendRequest("users/devices/edit/".$device, $fields);
	}
	public function getUsersPayments($page = 1, $filters = array(), $by = "time", $order = "DESC")
	{
		return $this->getItems("users/payments", $page, $filters, $by, $order);
	}
	public function getUsersTransactions($page = 1, $filters = array(), $by = "time", $order = "DESC")
	{
		return $this->getItems("users/transactions", $page, $filters, $by, $order);
	}
	public function getTvPlaylists($page = 1, $filters = array(), $by = "updated", $order = "DESC")
	{
		return $this->getItems("users/tv-playlists", $page, $filters, $by, $order);
	}
	public function addTvPlaylist($user = null, $fields = array(), $m3u = null)
	{
		$path = "users/tv-playlists/add";
		if ( $user !== null )
		{
			$path .= "/".$user;
		}
		return $this->sendRequest($path, $fields, array(), $m3u === null ? array() : array("m3u" => $m3u));
	}
	public function editTvPlaylist($playlist, $fields = array(), $m3u = null)
	{
		return $this->sendRequest("users/tv-playlists/edit/".$playlist, $fields, array(), $m3u === null ? array() : array("m3u" => $m3u));
	}
	public function getTvPlaylistByUser($user)
	{
		return $this->sendRequest("users/tv-playlists/user/".$user);
	}
	public function deleteTvPlaylist($playlist)
	{
		return $this->sendRequest("users/tv-playlists/delete/".$playlist);
	}
	public function getVodPlaylists($page = 1, $filters = array(), $by = "updated", $order = "DESC")
	{
		return $this->getItems("users/vod-playlists", $page, $filters, $by, $order);
	}
	public function addVodPlaylist($user = null, $fields = array(), $m3u = null)
	{
		$path = "users/vod-playlists/add";
		if ( $user !== null )
		{
			$path .= "/".$user;
		}
		return $this->sendRequest($path, $fields, array(), $m3u === null ? array() : array("m3u" => $m3u));
	}
	public function editVodPlaylist($playlist, $fields = array(), $m3u = null)
	{
		return $this->sendRequest("users/vod-playlists/edit/".$playlist, $fields, array(), $m3u === null ? array() : array("m3u" => $m3u));
	}
	public function getVodPlaylistByUser($user)
	{
		return $this->sendRequest("users/vod-playlists/user/".$user);
	}
	public function deleteVodPlaylist($playlist)
	{
		return $this->sendRequest("users/vod-playlists/delete/".$playlist);
	}
	public function editPlaylistDns($old, $new)
	{
		return $this->sendRequest("users/playlists/dns/edit", array("old" => $old, "new" => $new));
	}
	public function getPayments($page = 1, $filters = array())
	{
		return $this->getItems("payments", $page, $filters, null, null);
	}
	public function getTransactions($page = 1, $filters = array())
	{
		return $this->getItems("transactions", $page, $filters, null, null);
	}
	public function getRefillVias()
	{
		return $this->sendRequest("refill/vias");
	}
	public function getRefillMaxAmount()
	{
		return $this->sendRequest("refill/max");
	}
	public function refillDeposit($fields)
	{
		return $this->sendRequest("refill", $fields);
	}
	private function getItems($path, $page, $filters, $by, $order)
	{
		$query = array();
		if ( $filters )
		{
			$query["find"] = $filters;
		}
		if ( $by !== null && $by !== "" )
		{
			$query["by"] = $by;
		}
		if ( $order !== null && $order !== "" )
		{
			$query["order"] = strtoupper($order);
		}
		return $this->sendRequest($path."/".$page, array(), $query);
	}
	private function sendRequest($path, $fields = array(), $query = array(), $files = array())
	{
		$url = rtrim(self::API_URL, "/")."/".ltrim($path, "/");
		if ( $query )
		{
			$url .= "?".http_build_query($query);
		}
		$post = array_merge($fields, $files, array("token" => $this->token));
		$curl = curl_init($url);
		curl_setopt_array($curl, array(CURLOPT_POST => true, CURLOPT_POSTFIELDS => $post, CURLOPT_RETURNTRANSFER => true, CURLOPT_CONNECTTIMEOUT => 15, CURLOPT_TIMEOUT => 90));
		$body = curl_exec($curl);
		if ( $body === false )
		{
			$error = curl_error($curl);
			curl_close($curl);
			throw new \RuntimeException("API request failed: ".$error);
		}
		$status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		curl_close($curl);
		$response = json_decode($body, true);
		if ( json_last_error() !== JSON_ERROR_NONE )
		{
			throw new \RuntimeException("API returned invalid JSON");
		}
		if ( $status < 200 || $status >= 300 )
		{
			throw new \RuntimeException("API request failed with HTTP status ".$status);
		}
		return $response;
	}
}
?>