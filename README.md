# Landa DB

Mysql PDO CRUD library from Landa Systems

## Installation

Add cahkampung/landa-db to require in composer.json

"require": {
    "cahkampung/landa-db": "^1.0",
},

run composer install

## How To Use

### Insert

$db->insert(TABLE_NAME, DATA);

Example : 
$data = [
  'name' => 'john',
  'email' => 'john@example.com'
];

$db = new LandaDB;
$db->insert('user_table', $data);

### Update

$db->update(TABLE_NAME, DATA, PARAM);

Example : 
$data = [
  'name' => 'john',
  'email' => 'john@example.com'
];

$db = new LandaDB;
$db->update('user_table', $data, ['id' => 1]);
