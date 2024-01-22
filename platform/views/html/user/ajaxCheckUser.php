<?php

echo !empty($_SESSION['userId']) ? json_encode(array('result' => true)) : json_encode(array('result' => false));