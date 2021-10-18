<?php
$aliases['dev'] = array(
  'root' => '/var/aegir/projects/bd/dev/docroot',
  'uri' => 'bd.dev.cloud.bostondrupal.com',
  'remote-user' => 'aegir',
  'remote-host' => 'cloud.bostondrupal.com',
  'ssh-options'  => '-p 24',
  'path-aliases' => array(
    '%files' => 'sites/bd.dev.cloud.bostondrupal.com/files',
  ),
);
