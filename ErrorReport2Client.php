<?php

namespace ErrorReport2;

use SPFW\module\DB;
use SPFW\system\config\Config;
use SPFW\system\config\Environment;
use SPFW\system\Core;
use SPFW\system\CoreException;
use SPFW\system\error\AbstractAction;
use SPFW\system\error\Error;
use SPFW\system\routing\Request;


/**
 * ErrorReport2 Client
 *
 * @package ErrorReport2
 * @version 2.1.1
 */
final class ErrorReport2Client extends AbstractAction
{
	private const ER2_VERSION = '2.1.1';
	private const ER2_PROTOCOL_VERSION = 2;

	private const DEFAULT_SERVICE_ID = 'My SPFW App';
	private const DEFAULT_TIMEOUT_IN_SECONDS = 5;


	private string $server_url;
	private string $api_token;
	private string $service_identifier;
	private int $timeout = self::DEFAULT_TIMEOUT_IN_SECONDS;

	private bool $transmit_cookies = true;
	private bool $transmit_database_queries = true;
	private bool $transmit_environment = true;
	private bool $transmit_get_parameter = true;
	private bool $transmit_post_parameter = true;
	private bool $transmit_session_variables = true;

	private array $cookie_param_block_list = [];
	private array $get_param_block_list = [];
	private array $post_param_block_list = [];
	private array $session_param_block_list = [];


	public function __construct(string $server_url, string $api_token, string $service_identifier = self::DEFAULT_SERVICE_ID)
	{
		$this->server_url = $server_url;
		$this->api_token = $api_token;
		$this->service_identifier = $service_identifier;
	}

	public function applyToError(array $errors, ?string $request_hash) : bool
	{
		$data = $this->prepareAll($request_hash);

		$data['errors'] = $this->prepareErrorData($errors);

		return $this->send($data);
	}

	public function applyToThrowable(\Throwable $throwable, ?string $request_hash) : bool
	{
		$data = $this->prepareAll($request_hash);

		$data['throwable'] = $this->prepareThrowableData($throwable);

		return $this->send($data);
	}

	public function blockCookieParameter(string $param_name) : self
	{
		$this->cookie_param_block_list[] = $param_name;
		return $this;
	}

	public function blockGetParameter(string $param_name) : self
	{
		$this->get_param_block_list[] = $param_name;
		return $this;
	}

	public function blockPostParameter(string $param_name) : self
	{
		$this->post_param_block_list[] = $param_name;
		return $this;
	}

	public function blockSessionParameter(string $param_name) : self
	{
		$this->session_param_block_list[] = $param_name;
		return $this;
	}

	public function checkConfig(bool $strict = false) : bool
	{
		// TODO: Check for valid URI and API token
		return true;
	}

	public function disableTransmittingCookies() : self
	{
		$this->transmit_cookies = false;
		return $this;
	}

	public function disableTransmittingDatabaseQueries() : self
	{
		$this->transmit_database_queries = false;
		return $this;
	}

	public function disableTransmittingEnvironmentVariables() : self
	{
		$this->transmit_environment = false;
		return $this;
	}

	public function disableTransmittingGetParameter() : self
	{
		$this->transmit_get_parameter = false;
		return $this;
	}

	public function disableTransmittingPostParameter() : self
	{
		$this->transmit_post_parameter = false;
		return $this;
	}

	public function disableTransmittingSessionVariables() : self
	{
		$this->transmit_session_variables = false;
		return $this;
	}

	private function prepareAll(?string $session_id) : array
	{
		$request = Request::current();

		return [
				'authentication'	=> $this->prepareAuthenticationData(),
				'general'			=> $this->prepareGeneralData($session_id),
				'environment'		=> $this->prepareEnvironmentData(),
				'request'			=> $this->prepareRequestData($request),
				'database'			=> $this->prepareDatabaseData(),
				'cookies'			=> $this->prepareCookieData(),
				'get'				=> $this->prepareGetData(),
				'post'				=> $this->preparePostData(),
				'session'			=> $this->prepareSessionData(),
		];
	}

	private function prepareAuthenticationData() : array
	{
		return [
				'token'					=> $this->api_token,
				'service_id'			=> $this->service_identifier,
				'er2_version'			=> self::ER2_VERSION,
				'er2_protocol_version'	=> self::ER2_PROTOCOL_VERSION
		];
	}

	private function prepareCookieData() : ?array
	{
		if (!$this->transmit_cookies) {
			return null;
		}

		/** @noinspection GlobalVariableUsageInspection */
		return $this->prepareVariables($_COOKIE, $this->cookie_param_block_list);
	}

	private function prepareDatabaseData() : ?array
	{
		if (!$this->transmit_database_queries) {
			return null;
		}

		$database_data = [];
		foreach (DB::databases() as $database_index => $database) {
			$database_data[$database_index] = $database->getQueries();
		}

		return $database_data;
	}

	private function prepareEnvironmentData() : ?array
	{
		if (!$this->transmit_environment) {
			return null;
		}

		/** @noinspection GlobalVariableUsageInspection */
		return $_SERVER;
	}

	/**
	 * @param Error[] $errors
	 *
	 * @return array Json-serializable array
	 */
	private function prepareErrorData(array $errors) : array
	{
		$data = [];

		$filtered_errors = $this->filterErrorApplicants($errors);
		foreach ($filtered_errors as $error) {
			$data[] = [
					'fatal'		=> $error->isFatalError(),
					'error_no'	=> $error->getErrno(),
					'message'	=> $error->getErrstr(),
					'file'		=> $error->getErrfile(),
					'line'		=> $error->getErrline(),
			];
		}

		return $data;
	}

	/**
	 * @param null|string $session_id
	 *
	 * @return array<string,mixed>
	 */
	private function prepareGeneralData(?string $session_id) : array
	{
		try {
			$global_config = Core::activeInstance()->getEnvironment();
		} catch (CoreException $e) {
			$global_config = null;
		}

		$current_time = new \DateTime();

		/** @noinspection PhpDeprecationInspection */
		return [
				'er2_session_id'	=> $session_id ?? 'No session id',
				'timestamp'			=> $current_time->format('Y-m-d\TH:i:s'),
				'host_name'			=> php_uname('n'),
				'host_os'			=> PHP_OS,
				'host_os_release'	=> php_uname('r'),
				'host_os_version'	=> php_uname('v'),
				'php_version'		=> PHP_VERSION,
				'php_mode'			=> PHP_SAPI,
				'php_mem_usage'		=> memory_get_usage(),
				'environment_name'	=> $global_config instanceof Environment ? \get_class($global_config) : null,
				'debug_mode'		=> $global_config instanceof Config ? $global_config->isDebugMode() : null,
		];
	}

	private function prepareGetData() : ?array
	{
		if (!$this->transmit_get_parameter) {
			return null;
		}

		/** @noinspection GlobalVariableUsageInspection */
		return $this->prepareVariables($_GET, $this->get_param_block_list);
	}

	private function preparePostData() : ?array
	{
		if (!$this->transmit_post_parameter) {
			return null;
		}

		/** @noinspection GlobalVariableUsageInspection */
		return $this->prepareVariables($_POST, $this->post_param_block_list);
	}

	private function prepareRequestData(Request $request) : array
	{
		$url = $request->getUrl();
		return [
				'method'			=> $url->getHttpMethod(),
				'domain'			=> $url->getDomain(),
				'subdomain'			=> $url->getSubdomain(),
				'tcp_port'			=> $url->getTcpPort(),
				'path'				=> $url->getPath(),
				'cli'				=> $url->isCli(),
				'secure_connection'	=> $url->isSecure(),
		];
	}

	private function prepareSessionData() : ?array
	{
		if (!$this->transmit_session_variables) {
			return null;
		}

		/** @noinspection GlobalVariableUsageInspection */
		return $this->prepareVariables($_SESSION ?? [], $this->session_param_block_list);
	}

	private function prepareThrowableData(\Throwable $throwable) : array
	{
		return [
				'class_name'	=> \get_class($throwable),
				'error_no'		=> $throwable->getCode(),
				'message'		=> $throwable->getMessage(),
				'file'			=> $throwable->getFile(),
				'line'			=> $throwable->getLine(),
				'previous'		=> $throwable->getPrevious() !== null ? $this->prepareThrowableData($throwable->getPrevious()) : null,
		];
	}

	private function prepareVariables(array $variables, array $block_list) : array
	{
		$prepared_variables = [];
		foreach ($variables as $key => $value) {
			if (!\in_array($key, $block_list, true)) {
				$prepared_variables[$key] = var_export($value, true);
			}
		}

		return $prepared_variables;
	}

	private function send(array $data) : bool
	{
		$options = [
				'http' => [
					'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
					'method'  => 'POST',
					'timeout' => $this->timeout,
					'content' => json_encode($data, JSON_THROW_ON_ERROR)
				]
		];

		$context  = stream_context_create($options);

		return file_get_contents($this->server_url, false, $context);
	}

	public function setTimeout(int $timeout_in_seconds) : self
	{
		$this->timeout = $timeout_in_seconds;
		return $this;
	}
}


?>