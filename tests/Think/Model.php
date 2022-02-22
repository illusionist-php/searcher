<?php

namespace Tests\Think;

use Illusionist\Searcher\Contracts\Searchable as SearchableContract;
use Illusionist\Searcher\Think\Searchable;
use think\Model as ThinkModel;

abstract class Model extends ThinkModel implements SearchableContract
{
    use Searchable;

    protected $autoWriteTimestamp = true;

    protected $createTime = 'created_at';

    protected $updateTime = 'updated_at';
}
