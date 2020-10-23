<?php

namespace Swimmer\Models;

class Website extends AbstractModel implements ModelInterface
{
	protected $table = 'websites';
	protected $fields = ['id', 'title', 'identifier', 'repository', 'url', 'debug', 'created_at', 'updated_at'];
}
