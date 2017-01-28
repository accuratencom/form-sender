<?
if (isset($_COOKIE['form-sender-status'])) {
    $formSenderStatus=$_COOKIE['form-sender-status'];
    $formSenderMessage=$_COOKIE['form-sender-message'];
    setcookie('form-sender-status','',time());
    setcookie('form-sender-message','',time());
}
?><!doctype html><html>
<head>
    <style>
        * {
            box-sizing: border-box;
        }
        h2 {
            text-align: center;
        }
        form {
            display: block;
            width: 300px;
            margin: 0 auto;
        }
        form input {
            margin-bottom: 10px;
            display: block;
            width: 100%;
            padding: 5px 10px;
        }
        form textarea {
            display: block;
            width: 100%;
            padding: 5px 10px;
            margin-bottom: 10px;
        }
        form button {
            padding: 5px 10px;
            display: block;
            width: 100%;
        }
        .message {
            text-align: center;
            font-weight: bold;
            margin: 10px 0;
        }
        .message.error {
            color: #ff0000;
        }
        .message.success {
            color: #00bb00;
        }
        hr {
            margin: 50px 0;
        }
    </style>
    <script src="http://code.jquery.com/jquery-3.1.1.min.js"></script>
    <script>
        $(function() {
            var form2=$('#form2');
            form2.submit(function(e) {
                e.preventDefault();
                $(this).find('.message').html('').attr('class','message');
                $.post($(this).attr('action'),$(this).serialize(),function(json) {
                    form2.find('.message').addClass(json.status).html(json.message);
                    if (json.status=='success') {
                        form2[0].reset();
                    }
                },'json');
            });
        });
    </script>
</head>
<body>

<h2>Стандартная форма без AJAX</h2>
<form id="form1" action="form-sender.php" method="post">
    <input type="hidden" name="returnUrl" value="<?=$_SERVER['REQUEST_URI']?>"/>
    <input type="text" placeholder="Ваше имя" name="name" class="form-control">
    <input type="email" placeholder="Электронная почта" name="_replyto" class="form-control">
    <textarea rows="5" placeholder="Расскажите про свою задачу" name="message" class="form-control"></textarea>
    <button>Отправить</button>
</form>
<?
if (isset($formSenderStatus)) {
    echo "<div class='message ".$formSenderStatus."'>".$formSenderMessage."</div>";
}
?>
<hr>
<h2>Форма, отправляемая через AJAX</h2>
<form id="form2" action="form-sender.php" method="post">
    <input type="text" placeholder="Ваше имя" name="name" class="form-control">
    <input type="email" placeholder="Электронная почта" name="_replyto" class="form-control">
    <textarea rows="5" placeholder="Расскажите про свою задачу" name="message" class="form-control"></textarea>
    <button>Отправить</button>
    <div class="message"></div>
</form>
</body>
</html>