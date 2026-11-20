<?php

$pair = sodium_crypto_sign_keypair();

echo 'secret=' . base64_encode(sodium_crypto_sign_secretkey($pair)) . PHP_EOL;
echo 'public=' . base64_encode(sodium_crypto_sign_publickey($pair)) . PHP_EOL;
