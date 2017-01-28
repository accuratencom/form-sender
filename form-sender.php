<?
require_once('vendor/autoload.php');

/**
 * Класс для обработки контактной формы
 */
class formSender {

    private $fieldsA=array();
    private $requiredFieldsA=array();
    private $destinationsA=array(); // Сервисы-получатели
    private $fieldSourceA; // Массив-источник данных для скрипта - POST, GET или REQUEST
    private $returnUrl; // url для возврата после выполнения обработки
    private $specialFieldsA;

    /**
     * Инициализация скрипта отправки
     * @param string $fieldSource Источник данных скрипта - POST, GET, REQUEST
     */
    function __construct($fieldSource="POST") {
        require_once('config.php');
        $this->specialFieldsA=array(
            'site_url'=>$_SERVER['REQUEST_SCHEME'].'://'.$_SERVER['HTTP_HOST'],
        );
        if (isset($_REQUEST['returnUrl'])) {
            $this->returnUrl=$_REQUEST['returnUrl'];
        }
        $this->setFieldsSource($fieldSource);
    }

    /**
     * Запуск отправки сообщения
     */
    function send() {

        if (count($this->destinationsA)==0) { // Если не задано ни одного сервиса-получателя
            $this->fireResult("error","Не задано ни одного сервиса-получателя. Для задания используйте функцию setDestinations");
        }

        $isFilled=true; // Все ли обязательные поля заполены
        foreach($this->requiredFieldsA as $field) {
            if (!isset($this->fieldsA[$field])||$this->fieldsA[$field]=='') {
                $isFilled=false;
                break;
            }
        }

        if (!$isFilled) {
            $this->fireResult("error",'Не все обязательные поля заполнены');
        }

        if (!$this->checkAkismet()) { // Проверяем сообщение на спам
            foreach($this->destinationsA as $destination) {
                $destinationFunction='sendTo'.ucfirst($destination);
                $this->$destinationFunction();
            }
            $this->fireResult("success","Сообщение успешно отправлено");
        } else {
            $this->$this->fireResult("error","Сообщение определно как спам");
        }
    }

    /**
     * Выдача результата работы скрипта в виде JSON или перенаправление
     * @param string $status Статус завершения
     * @param string $message Текст сообщения
     */
    function fireResult($status,$message="") {
        if (isset($this->returnUrl)) {
            setcookie('form-sender-status',$status,0,'/');
            setcookie('form-sender-message',$message,0,'/');
            header('location: '.$this->returnUrl);
            exit;
        } else {
            $data = array(
                'status' => $status,
                'message' => $message
            );
            header('Content-Type: application/json;charset=utf-8');
            echo json_encode($data);
            if ($status=='error') {exit;}
        }
    }

    /**
     * Задание сервисов-получателей сообщения
     * @param string[] $destinationsA
     */
    function setDestinations($destinationsA) {
        $this->destinationsA=$destinationsA;
    }

    /**
     * Изменеие источника данных для скрипта
     * @param string $source Источник данных - POST, GET, REQUEST
     */
    private function setFieldsSource($source="POST") {
        $source=strtolower($source);
        if ($source=='get') {
            $this->fieldSourceA=$_GET;
        } elseif ($source=='post') {
            $this->fieldSourceA=$_POST;
        } else {
            $this->fieldSourceA=$_REQUEST;
        }
    }

    /**
     * Задание имени пользователя
     * @param string $fieldName Имя поля в форме
     * @param bool $isRequired Обязательное ли поле, по умолчанию нет
     */
    function setNameField($fieldName, $isRequired=false) {
        $this->setField($fieldName,$isRequired,'name');
        if ($fieldName!='name') { // Если поле имени имеет нестандартное название, то его сохраняем под обоими названиями, чтобы можно было использовать в шаблоне любое
            $this->setField($fieldName);
        }
    }

    /**
     * Задание email пользователя
     * @param string $fieldName Имя поля в форме
     * @param bool $isRequired Обязательное ли поле, по умолчанию нет
     */
    function setEmailField($fieldName, $isRequired=false) {
        $this->setField($fieldName,$isRequired,'email');
        if ($fieldName!='email') { // Если поле email имеет нестандартное название, то его сохраняем под обоими названиями, чтобы можно было использовать в шаблоне любое
            $this->setField($fieldName);
        }
    }

    /**
     * Задание отправляемого сообщения
     * @param string $fieldName Имя поля в форме
     * @param bool $isRequired Обязательное ли поле, по умолчанию нет
     */
    function setMessageField($fieldName, $isRequired=false) {
        $this->setField($fieldName,$isRequired,'message');
        if ($fieldName!='message') { // Если поле сообщения имеет нестандартное название, то его сохраняем под обоими названиями, чтобы можно было использовать в шаблоне любое
            $this->setField($fieldName);
        }
    }

    /**
     * Задание произвольного поля из формы
     * @param string $name Имя поля в отправляемом сообщении (по умолчанию равно имени поля в форме)
     * @param bool $isRequired Обязательное ли поле, по умолчанию нет
     * @param string $fieldName Имя поля в форме
     */
    function setField($fieldName, $isRequired=false,$name="") {
        if ($name=='') {$name=$fieldName;}
        $this->fieldsA[$name]=trim(htmlspecialchars(strip_tags($this->fieldSourceA[$fieldName])));
        if ($isRequired) {
            $this->requiredFieldsA[] = $name;
        }
    }

    /**
     * Проверка на спам через сервис Akismet
     * @return bool Возвращает true, если сообщение определено как спам, в ином случае возвращает false
     */
    function checkAkismet() {
        $site_url=$_SERVER['REQUEST_SCHEME'].'://'.$_SERVER['HTTP_HOST'];
        $akismet = new AntonioTajuelo\Akismet\Akismet(AKISMET_KEY,$site_url);
        $params = [
            'is_test'        => 1,
            'user_ip'        => $_SERVER['REMOTE_ADDR'],
            'user_agent'     => $_SERVER['HTTP_USER_AGENT'],
            'referrer'     => $_SERVER['HTTP_REFERER'],
            'comment_type ' => 'contact-form'
        ];
        if (isset($this->fieldsA['name'])) {
            $params['comment_author']=$this->fieldsA['name'];
        }
        if (isset($this->fieldsA['email'])) {
            $params['comment_email']=$this->fieldsA['email'];
        }
        if (isset($this->fieldsA['message'])) {
            $params['comment_content']=$this->fieldsA['message'];
        }
        $isSpamComment = $akismet->commentCheck($params);
        return $isSpamComment==='true';
    }

    /**
     * Отправка сообщения через сервис Mailgun
     */
    function sendToMailgun() {
        $mg = new Mailgun\Mailgun(MAILGUN_KEY);
        $params=array();
        //$params['from']=(isset($this->fieldsA['email'])&&$this->fieldsA['email']!='')?$this->fieldsA['email']:MAILGUN_TO;
        $params['from']=MAILGUN_TO;
        $params['to']=MAILGUN_TO;
        $params['subject']=$this->processContent(MAILGUN_SUBJECT);
        $params['html']=$this->getTemplate('mailgun');
        $mg->sendMessage(MAILGUN_DOMAIN, $params);
    }

    /**
     * Отправка сообщения в сервис Slack
     */
    function sendToSlack() {
        $settings = [
            'username' => SLACK_USERNAME,
            'channel' => SLACK_CHANNEL,
            'link_names' => true
        ];

        $message=$this->getTemplate('slack');
        $client = new Maknz\Slack\Client(SLACK_WEBHOOK_URL, $settings);
        $client->send($message);
    }

    /**
     * Получени шаблона по имени и подмена в нем названий полей, обрамленных знаком %
     * @param string $name Имя шаблона, расположенного в папке templates
     * @return mixed|string Возвращает обработанный текст шаблона
     */
    function getTemplate($name) {
        $content=file_get_contents('templates/'.$name.'.html');
        $content=$this->processContent($content);
        return $content;
    }

    /**
     * Подмена в тексте названий полей, обрамленных двойными фигурными скобками, например, {{name}}
     * @param string $content Обрабатываемый текст
     * @return string mixed Обработанный текст
     */
    private function processContent($content) {
        foreach($this->fieldsA as $key=>$value) {
            $content=str_replace("{{".$key."}}",$value,$content);
        }
        foreach($this->specialFieldsA as $key=>$value) {
            $content=str_replace("{{".$key."}}",$value,$content);
        }
        return $content;
    }

}

$formSender=new formSender('post');
$formSender->setNameField('name',true);
$formSender->setEmailField('_replyto');
$formSender->setMessageField('message',true);
$formSender->setDestinations(array('slack','mailgun'));
$formSender->send();