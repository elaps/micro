<?php
return [
  //'/' => 'site/index',
  '/home' => 'site/test',
  'POST /home' => 'site/test2',
  '/new/{:id}/{:name}' => 'site/new',
  '/news/{:id;[0-9]+}' => 'site/news',
];