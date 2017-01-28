# akismet-php
Stop spam from hitting your website comments or content using Akismet.

## Getting Started

First, get your Akismet key (if you still do not have one) from here: https://akismet.com/

Then install the package using Composer and check the following code samples.

## Code Sample

```php
$akismet = new Akismet('YOUR-AKISMET-KEY','YOUR-WEBSITE-URL');

// Check if the Akismet key is valid
$isValidKey = $akismet->verifyKey();
var_dump($isValidKey);

// Check if a comment is spam
$params = [
  'is_test'        => 1,
  'user_ip'        => '123.123.123.123',
  'user_agent'     => 'blablabla',
  'comment_author' => 'viagra-test-123',
  'coment_content' => 'spam'
];
$isSpamComment = $akismet->commentCheck($params);
var_dump($isSpamComment);
```

## Available Methods

- verifyKey
- commentCheck
- submitSpam
- submitHam

## Please View

Akismet API Documentation: https://akismet.com/development/api/#detailed-docs