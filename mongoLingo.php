<?php
/**
 * User    : Seyhan 'sTaRs' YILDIZ
 * Mail    : syhnyldz@gmail.com
 * Company : Digital Panzehir
 * Web     : www.digitalpanzehir.com
 * Date    : 11/2/2018
 * Time    : 10:25
 */

namespace akalod;

use MongoDB\BSON\ObjectId;
use MongoDB\Client;

class mongoLingo
{
    private $client = null;
    public static $DSN = 'mongodb://demo.digitalpanzehir.com:27017';
    public static $db_name = 'arge';


    public static $ORM;
    private $stack;
    private $joins = [];
    private $wheres = \stdClass::class;

    /**
     * @param $id
     * @return ObjectId
     */
    public static function ObjectID($id)
    {
        return new ObjectId($id);
    }

    /**
     * select collection like table
     * @param $name
     * @return $this
     */
    public function collection($name)
    {
        $this->stack = $this->client->selectCollection($name);
        return $this;
    }

    /**
     * @param string $method
     * @return mixed
     * @throws \Exception
     */
    private function prepare($method = 'findOne')
    {
        if ($this->joins) {
            if ($method == 'findOne' || $method == 'find') {
                $r = $this->joins;
                $r[] = ['$match' => $this->wheres];
                return $this->stack->aggregate($r);
            } else {
                throw new \Exception('You can not run this command with have been joined table');
            }
        }
        return $this->stack->$method($this->wheres);
    }

    /**
     * @return mixed
     * @throws \Exception
     */
    public function first()
    {
        try {
            $r = $this->prepare();
        } catch (\Exception $e) {
            throw $e;
        }
        return $this->workStructure($r);
    }

    /**
     *  objectId has been changed by string for easy access
     * @param $data
     */
    private function setStructure(&$data)
    {
        if (isset($data) && $data['_id']) {
            $y = (array)$data['_id'];
            $data['_id'] = $y['oid'];
        }
    }

    /**
     * @param $data
     * @return array
     */
    private function workStructure($data)
    {
        $temp = [];
        if (is_array($data)) {
            foreach ($data as $i) {
                $this->setStructure($i);
                $temp[] = $i;
            };

        } else {
            $this->setStructure($data);
        }

        return $temp;
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function get()
    {

        try {
            $r = $this->prepare('find');
        } catch (\Exception $e) {
            throw $e;
        }

        return $this->workStructure($r);

    }

    /**
     * @param null $dsn
     * @param null $db
     * @return mongoLingo
     */
    public static function init($dsn = null, $db = null)
    {
        self::$ORM = new self();
        self::$ORM->wheres = new \stdClass();
        if($dsn)
            self::$DSN = $dsn;
        $client = new Client(self::$DSN);
        if ($db)
            self::$db_name = $db;
        self::$ORM->client = $client->selectDatabase(self::$db_name);

        return self::$ORM;
    }

    public function distinct($column_name)
    {
        return $this->stack->distinct($column_name);
    }

    /**
     * @param $table
     * @param $first (column name on joined table)
     * @param $second (column name on joiner table)
     * @param null $alias
     * @return $this
     */
    public function leftJoin($table, $first, $second, $alias = null)
    {
        $this->joins[] = ['$lookup' => [
            'from' => $table,
            'localField' => $first,
            'foreignField' => $second,
            'as' => $alias ? $alias : 'join_' . count($this->joins)
        ]];

        return $this;
    }

    /**
     * @param $data -> can be array like as ["_id"=>self::ObjectID("5bdc01ef45bfc30ea8004d92")]
     * @param null $value
     * @return $this
     */
    public function where($data, $value = null)
    {
        if (is_array($data)) {
            foreach ($data as $k => $v) {
                $this->wheres->$k = $v;
            }
        } else {
            $this->wheres->$data = $value;
        }
        return $this;
    }

    /**
     * @return mixed
     * @throws \Exception
     */
    public function deleteAll()
    {
        try {
            return $this->prepare('deleteAll');
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @return mixed
     * @throws \Exception
     */
    public function delete()
    {
        try {
            return $this->prepare('deleteOne');
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function insert($data)
    {
        if ($this->stack) {
            $this->stack->insertOne($data);
        }
    }

}