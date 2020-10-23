<?php

namespace Swimmer\Models;

class Template extends AbstractModel implements ModelInterface
{
	protected $table = 'templates';
	protected $fields = ['id', 'title', 'subject', 'body', 'css', 'to', 'reply_to', 'fields', 'required_fields', 'created_at', 'updated_at'];
}
