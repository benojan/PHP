<?php
  $str = 'hi中国你好';
  preg_match_all('/./u', $str, $tmp);
  print_r($tmp[0]);
?>
