<?php

namespace Tests\Eloquent;

use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Database\SQLiteConnection;
use Illusionist\Searcher\Eloquent\Searchable;
use PDO;

abstract class Model extends EloquentModel
{
    use Searchable;

    /**
     * Get the database connection for the model.
     *
     * @return \Illuminate\Database\Connection
     */
    public function getConnection()
    {
        return new SQLiteConnection(new PDO('sqlite::memory:'));
    }
}
