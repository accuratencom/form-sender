<?php namespace AntonioTajuelo\Akismet;

class Akismet
{

  private $key  = '';
  private $blog = '';

  function __construct($key = '',$blog = '')
  {
    $this->key  = $key;
    $this->blog = $blog;
  }

  private function request($endpoint,$params = [])
  {
    $client = new \GuzzleHttp\Client(['http_errors' => false]);
    $form_params = array_merge(['blog' => $this->blog],$params);
    $request = $client->request(
      'POST',
      $endpoint,
      [
        'form_params' => $form_params
      ]
    );
    return (string)$request->getBody();
  }

  public function verifyKey()
  {
    return $this->request(
      'https://rest.akismet.com/1.1/verify-key',
      ['key' => $this->key]
      );
  }

  public function commentCheck($params)
  {
    return $this->request(
      'https://' . $this->key . '.rest.akismet.com/1.1/comment-check',
      $params
      );
  }

  public function submitSpam($params)
  {
    return $this->request(
      'https://' . $this->key . '.rest.akismet.com/1.1/submit-spam',
      $params
      );
  }

  public function submitHam($params)
  {
    return $this->request(
      'https://' . $this->key . '.rest.akismet.com/1.1/submit-ham',
      $params
      );
  }

}