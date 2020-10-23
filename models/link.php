<?php

namespace Swimmer\Models;

class Link extends AbstractModel implements ModelInterface
{
	protected $table = 'links';
	protected $fields = ['id', 'title', 'url', 'is_portfolio', 'sort', 'created_at', 'updated_at'];
}
