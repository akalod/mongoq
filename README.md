# mongoq
MongoDB Query Builder (PHP)

# install
```php
composer require akalod/mongoq
```

# using examples with alias
```php
Use akalod/mongoq as DB;

// static using example
DB::init("mongodb://localhost","db_name");
DB::$Q->collection('tasks')->get();

// construct example of multi line connections
$x = DB::init("mongodb://localhost","a_db");
$r = DB::init("mongodb://username:password@remote.server.com","b_db");

$r->collection('tasks')->get();
// or 
$x->collection('a_table')
  ->leftJoin('b_table','user_id',$r::ObjectID('_id'))
  ->where('user_id',1)
  ->get();
  
// you can use multiple where condition 
... ->where('status',1)->where('owner_id',2) 
//or 
.. ->where(['status'=>1,'owner_id'=>2])


```
