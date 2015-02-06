<?php
/**
 * Created by: Kalekin Mikhail
 * Email: mangooz@yandex.ru
 * Date: 2/3/15
 * Time: 9:52 AM
 */


class Tinkoff {
    private $_terminal_name,
            $_secret_key,
            $_api_url,
            $_params;

    public function __construct($terminal_name, $secret_key, $api_url = 'https://rest-api-test.tcsbank.ru/rest')
    {
        $this->setTerminalName($terminal_name);
        $this->setSecretKey($secret_key);
        $this->setApiUrl($api_url);
        $this->initialize();
    }

    private function setTerminalName($terminal_name)
    {
        $this->_terminal_name = $terminal_name;
    }

    private function setSecretKey($secret_key)
    {
        $this->_secret_key = $secret_key;
    }

    private function setApiUrl($api_url)
    {
        $this->_api_url = $api_url;
    }

    public function setParam($param_name, $param_value)
    {
        $this->_params[$param_name] = $param_value;
        return $this;
    }

    /**
     * Очищает введенные ранее параметры и добавляет новые из $params
     * @param $params
     * @return $this
     */
    public function setParams($params)
    {
        $this->initialize();
        foreach ($params as $name => $param) {
            $this->setParam($name, $param);
        }
        return $this;
    }
    private function initialize()
    {
        $this->_params = [
            'TerminalKey' => $this->_terminal_name
        ];
    }

    public function generateToken()
    {
        $params = $this->_params;
        $params['Password'] = $this->_secret_key;
        ksort($params);
        $str_values = implode($params);
        return hash('sha256', $str_values);
    }

    /**
     * Возвращает установленные параметры запроса (без токена)
     * @return mixed
     */
    public function getRequestParams()
    {
        return $this->_params;
    }

    /**
     * @param $method - (Init, Charge, GetState, etc...)
     * @return mixed - возвращает ответ апишки в виде StdObject
     * @throws \Exception
     */
    public function send($method)
    {
        $response = json_decode($this->makeHTTPRequest($method));
        if ($response === null) {
            throw new \Exception('Json parse error');
        }
        $this->initialize(); // очистим пармаметры для следущего запроса
        return $response;
    }

    private function makeHTTPRequest($method)
    {
        $ch = curl_init($this->_api_url . '/' . $method);
        //curl_setopt($ch, CURLOPT_VERBOSE, 1); // for debug
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS,
            http_build_query(
                array_merge($this->getRequestParams(), ['Token' => $this->generateToken()])
            )
        );
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        if ($error = curl_error($ch)) {
            throw new \Exception($error);
        }
        $response = curl_exec($ch);
        if ($response === false) {
            throw new \Exception('Connect error');
        }
        curl_close($ch);
        return $response;
    }

}