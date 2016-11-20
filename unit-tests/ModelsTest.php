<?php

/*
  +------------------------------------------------------------------------+
  | Phalcon Framework                                                      |
  +------------------------------------------------------------------------+
  | Copyright (c) 2011-2015 Phalcon Team (http://www.phalconphp.com)       |
  +------------------------------------------------------------------------+
  | This source file is subject to the New BSD License that is bundled     |
  | with this package in the file docs/LICENSE.txt.                        |
  |                                                                        |
  | If you did not receive a copy of the license and are unable to         |
  | obtain it through the world-wide-web, please send an email             |
  | to license@phalconphp.com so we can send you a copy immediately.       |
  +------------------------------------------------------------------------+
  | Authors: Andres Gutierrez <andres@phalconphp.com>                      |
  |          Eduar Carvajal <eduar@phalconphp.com>                         |
  +------------------------------------------------------------------------+
*/

use Phalcon\Mvc\Model\Message as ModelMessage;

class Issue_1534 extends \Phalcon\Mvc\Model
{
}

class ModelsTest extends PHPUnit_Framework_TestCase
{

	public function __construct()
	{
		spl_autoload_register(array($this, 'modelsAutoloader'));
	}

	public function __destruct()
	{
		spl_autoload_unregister(array($this, 'modelsAutoloader'));
	}

	public function tearDown()
	{
		Phalcon\Mvc\Model::setup(array(
			'phqlLiterals' => true,
		));
	}

	public function modelsAutoloader($className)
	{
		if (file_exists('unit-tests/models/' . $className . '.php')) {
			require 'unit-tests/models/' . $className . '.php';
		}
	}

	protected function _prepareDb($db)
	{
		$db->delete("personas", "estado='X'");
		$db->delete("personas", "cedula LIKE 'CELL%'");
	}

	protected function _getDI($dbService)
	{

		Phalcon\DI::reset();

		$di = new Phalcon\DI();

		$di->set('modelsManager', function() {
			return new Phalcon\Mvc\Model\Manager();
		});

		$di->set('modelsMetadata', function() {
			return new Phalcon\Mvc\Model\Metadata\Memory();
		});

		$di->set('db', $dbService, true);

		return $di;
	}

	public function testModelsMysql()
	{
		require 'unit-tests/config.db.php';
		if (empty($configMysql)) {
			$this->markTestSkipped("Skipped");
			return;
		}

		$di = $this->_getDI(function(){
			require 'unit-tests/config.db.php';
			$db = new Phalcon\Db\Adapter\Pdo\Mysql($configMysql);
			return $db;
		});

		$this->_executeTestsNormal($di);
		$this->_executeTestsRenamed($di);

		$this->issue1534($di);
		$this->issue886($di);
		$this->issue11253($di);
	}

	public function ytestModelsPostgresql()
	{
		require 'unit-tests/config.db.php';
		if (empty($configPostgresql)) {
			$this->markTestSkipped("Skipped");
			return;
		}

		$di = $this->_getDI(function(){
			require 'unit-tests/config.db.php';
			return new Phalcon\Db\Adapter\Pdo\Postgresql($configPostgresql);
		});

		$this->_executeTestsNormal($di);
		$this->_executeTestsRenamed($di);

		$this->issue886($di);
	}

	public function testModelsSqlite()
	{
		require 'unit-tests/config.db.php';
		if (empty($configSqlite)) {
			$this->markTestSkipped("Skipped");
			return;
		}

		$di = $this->_getDI(function(){
			require 'unit-tests/config.db.php';
			return new Phalcon\Db\Adapter\Pdo\Sqlite($configSqlite);
		});

		$this->_executeTestsNormal($di);
		$this->_executeTestsRenamed($di);

		$this->issue886($di);
	}

	public function testIssue10371()
	{
		$this->assertTrue(in_array('addBehavior', get_class_methods('Phalcon\Mvc\Model')));
	}

	protected function issue11253($di)
	{
		$db = $di->getShared('db');
		$this->_prepareDb($di->getShared('db'));

		$modelsManager = $di->getShared('modelsManager');

		$childsRepository = $modelsManager->getRepository(
			Childs::class
		);

		$child = new Childs();
		$child->for = '1';
		$modelsManager->create($child);

		$child = new Childs();
		$child->group = '1';
		$modelsManager->create($child);

		$children = $childsRepository->findByFor(1);
		$children = $childsRepository->findByGroup(1);
	}

	protected function issue1534($di)
	{
		$db = $di->getShared('db');
		$this->_prepareDb($di->getShared('db'));

		$modelsManager = $di->getShared('modelsManager');

		$issue1534Repository = $modelsManager->getRepository(
			Issue_1534::class
		);

		$this->assertTrue($db->delete('issue_1534'));

		$product = new Issue_1534();
		$product->language  = new \Phalcon\Db\RawValue('default(language)');
		$product->language2 = new \Phalcon\Db\RawValue('default(language2)');
		$product->name      = 'foo';
		$product->slug      = 'bar';
		$product->brand     = new \Phalcon\Db\RawValue('default');
		$product->sort      = new \Phalcon\Db\RawValue('default');
		$this->assertTrue($modelsManager->save($product));
		$this->assertEquals(
			1,
			$issue1534Repository->count()
		);

		$entry = $issue1534Repository->findFirst();
		$this->assertEquals('bb', $entry->language);
		$this->assertEquals('bb', $entry->language2);
		$this->assertEquals('0', $entry->sort);
		$this->assertTrue($entry->brand === NULL);

		$this->assertTrue(
			$di->get("modelsManager")->delete($entry)
		);

		$product = new Issue_1534();
		$product->language  = 'en';
		$product->language2 = 'en';
		$product->name      = 'foo';
		$product->slug      = 'bar';
		$product->brand     = 'brand';
		$product->sort      = 1;
		$this->assertTrue($modelsManager->save($product));
		$this->assertEquals(
			1,
			$issue1534Repository->count()
		);

		$entry = $issue1534Repository->findFirst();
		$entry->brand    = new \Phalcon\Db\RawValue('default');
		$entry->sort     = new \Phalcon\Db\RawValue('default');
		$this->assertTrue($modelsManager->save($entry));
		$this->assertEquals(
			1,
			$issue1534Repository->count()
		);

		$entry = $issue1534Repository->findFirst();
		$this->assertEquals('0', $entry->sort);
		$this->assertTrue($entry->brand === NULL);

		$entry->language2 = new \Phalcon\Db\RawValue('default(language)');
		$this->assertTrue($modelsManager->save($entry));
		$this->assertEquals(
			1,
			$issue1534Repository->count()
		);

		$entry = $issue1534Repository->findFirst();
		$this->assertEquals('bb', $entry->language2);
		$this->assertEquals('0', $entry->sort);
		$this->assertTrue($entry->brand === NULL);
		$di->get("modelsManager")->delete($entry);

		//test subject of Issue - setting RawValue('default')
		$product = new Issue_1534();
		$product->language = new \Phalcon\Db\RawValue('default');
		$product->language2 = new \Phalcon\Db\RawValue('default');
		$product->name     = 'foo';
		$product->slug     = 'bar';
		$product->brand    = 'brand';
		$product->sort     = 1;
		$this->assertTrue($modelsManager->save($product));
		$this->assertEquals(
			1,
			$issue1534Repository->count()
		);


		$entry = $issue1534Repository->findFirst();
		$this->assertEquals('bb', $entry->language);
		$this->assertEquals('bb', $entry->language2);

		$entry->language2 = 'en';
		$this->assertTrue($modelsManager->save($entry));

		$entry = $issue1534Repository->findFirst();
		$this->assertEquals('en', $entry->language2);

		$entry->language2 = new \Phalcon\Db\RawValue('default');
		$this->assertTrue($modelsManager->save($entry));

		$entry = $issue1534Repository->findFirst();
		$this->assertEquals('bb', $entry->language2);


		$this->assertTrue($db->delete('issue_1534'));
	}

	protected function issue886($di)
	{
		$this->_prepareDb($di->getShared('db'));

		Phalcon\Mvc\Model::setup(array(
			'phqlLiterals' => false,
		));

		$peopleRepository = $di->get("modelsManager")->getRepository(
			People::class
		);

		$people = $peopleRepository->findFirst();
		$this->assertTrue(is_object($people));
		$this->assertEquals(get_class($people), 'People');
	}

	protected function _executeTestsNormal($di)
	{

		$this->_prepareDb($di->getShared('db'));

		$modelsManager = $di->getShared('modelsManager');

		$peopleRepository = $modelsManager->getRepository(
			People::class
		);

		$personasRepository = $modelsManager->getRepository(
			Personas::class
		);

		$robotsRepository = $modelsManager->getRepository(
			Robots::class
		);

		//Count tests
		$this->assertEquals(
			$peopleRepository->count(),
			$personasRepository->count()
		);

		$params = array();
		$this->assertEquals(
			$peopleRepository->count($params),
			$personasRepository->count($params)
		);

		$params = array("estado='I'");
		$this->assertEquals(
			$peopleRepository->count($params),
			$personasRepository->count($params)
		);

		$params = ["estado='I'"];
		$this->assertEquals(
			$peopleRepository->count($params),
			$personasRepository->count($params)
		);

		$params = array("conditions" => "estado='I'");
		$this->assertEquals(
			$peopleRepository->count($params),
			$personasRepository->count($params)
		);

		//Find first
		$people = $peopleRepository->findFirst();
		$this->assertTrue(is_object($people));
		$this->assertEquals(get_class($people), 'People');

		$persona = $personasRepository->findFirst();
		$this->assertEquals($people->nombres, $persona->nombres);
		$this->assertEquals($people->estado, $persona->estado);

		$people = $peopleRepository->findFirst(
			[
				"estado='I'",
			]
		);
		$this->assertTrue(is_object($people));

		$persona = $personasRepository->findFirst(
			[
				"estado='I'",
			]
		);
		$this->assertTrue(is_object($persona));

		$this->assertEquals($people->nombres, $persona->nombres);
		$this->assertEquals($people->estado, $persona->estado);

		$people = $peopleRepository->findFirst(
			[
				"estado='I'",
			]
		);
		$persona = $personasRepository->findFirst(
			[
				"estado='I'",
			]
		);
		$this->assertEquals($people->nombres, $persona->nombres);
		$this->assertEquals($people->estado, $persona->estado);

		$params = array("conditions" => "estado='I'");
		$people = $peopleRepository->findFirst($params);
		$persona = $personasRepository->findFirst($params);
		$this->assertEquals($people->nombres, $persona->nombres);
		$this->assertEquals($people->estado, $persona->estado);

		$params = array("conditions" => "estado='A'", "order" => "nombres");
		$people = $peopleRepository->findFirst($params);
		$persona = $personasRepository->findFirst($params);
		$this->assertEquals($people->nombres, $persona->nombres);
		$this->assertEquals($people->estado, $persona->estado);

		$params = array("estado='A'", "order" => "nombres DESC", "limit" => 30);
		$people = $peopleRepository->findFirst($params);
		$persona = $personasRepository->findFirst($params);
		$this->assertEquals($people->nombres, $persona->nombres);
		$this->assertEquals($people->estado, $persona->estado);

		$params = array("estado=?1", "bind" => array(1 => 'A'), "order" => "nombres DESC", "limit" => 30);
		$people = $peopleRepository->findFirst($params);
		$persona = $personasRepository->findFirst($params);
		$this->assertEquals($people->nombres, $persona->nombres);
		$this->assertEquals($people->estado, $persona->estado);

		$params = array("estado=:estado:", "bind" => array("estado" => 'A'), "order" => "nombres DESC", "limit" => 30);
		$people = $peopleRepository->findFirst($params);
		$persona = $personasRepository->findFirst($params);
		$this->assertEquals($people->nombres, $persona->nombres);
		$this->assertEquals($people->estado, $persona->estado);

		$robot = $robotsRepository->findFirst(
			[
				1,
			]
		);
		$this->assertEquals(get_class($robot), 'Robots');

		//Find tests
		$personas = $personasRepository->find();
		$people = $peopleRepository->find();
		$this->assertEquals(count($personas), count($people));

		$personas = $personasRepository->find(
			[
				"estado='I'",
			]
		);
		$people = $peopleRepository->find(
			[
				"estado='I'",
			]
		);
		$this->assertEquals(count($personas), count($people));

		$personas = $personasRepository->find(
			array("estado='I'")
		);
		$people = $peopleRepository->find(
			array("estado='I'")
		);
		$this->assertEquals(count($personas), count($people));

		$personas = $personasRepository->find(
			array(
				"estado='A'",
				"order" => "nombres",
			)
		);
		$people = $peopleRepository->find(
			array(
				"estado='A'",
				"order" => "nombres",
			)
		);
		$this->assertEquals(count($personas), count($people));

		$personas = $personasRepository->find(
			array(
				"estado='A'",
				"order" => "nombres",
				"limit" => 100,
			)
		);
		$people = $peopleRepository->find(
			array(
				"estado='A'",
				"order" => "nombres",
				"limit" => 100,
			)
		);
		$this->assertEquals(count($personas), count($people));

		$params = array("estado=?1", "bind" => array(1 => "A"), "order" => "nombres", "limit" => 100);
		$personas = $personasRepository->find($params);
		$people = $peopleRepository->find($params);
		$this->assertEquals(count($personas), count($people));

		$params = array("estado=:estado:", "bind" => array("estado" => "A"), "order" => "nombres", "limit" => 100);
		$personas = $personasRepository->find($params);
		$people = $peopleRepository->find($params);
		$this->assertEquals(count($personas), count($people));

		$number = 0;
		$peoples = $personasRepository->find(
			array("conditions" => "estado='A'", "order" => "nombres", "limit" => 20)
		);
		foreach($peoples as $people){
			$number++;
		}
		$this->assertEquals($number, 20);

		$persona = new Personas($di);
		$persona->cedula = 'CELL' . mt_rand(0, 999999);
		$this->assertFalse($modelsManager->save($persona));

		//Messages
		$this->assertEquals(count($persona->getMessages()), 3);

		$messages = array(
			0 => ModelMessage::__set_state(array(
				'_type' => 'PresenceOf',
				'_message' => 'tipo_documento_id is required',
				'_field' => 'tipo_documento_id',
				'_code' => 0,
			)),
			1 => ModelMessage::__set_state(array(
				'_type' => 'PresenceOf',
				'_message' => 'cupo is required',
				'_field' => 'cupo',
				'_code' => 0,
			)),
			2 => ModelMessage::__set_state(array(
				'_type' => 'PresenceOf',
				'_message' => 'estado is required',
				'_field' => 'estado',
				'_code' => 0,
			)),
		);
		$this->assertEquals($persona->getMessages(), $messages);

		//Save
		$persona = new Personas($di);
		$persona->cedula = 'CELL' . mt_rand(0, 999999);
		$persona->tipo_documento_id = 1;
		$persona->nombres = 'LOST';
		$persona->telefono = '1';
		$persona->cupo = 20000;
		$persona->estado = 'A';
		$this->assertTrue($modelsManager->save($persona));

		$persona = new Personas($di);
		$persona->cedula = 'CELL' . mt_rand(0, 999999);
		$persona->tipo_documento_id = 1;
		$persona->nombres = 'LOST LOST';
		$persona->telefono = '2';
		$persona->cupo = 0;
		$persona->estado = 'X';
		$this->assertTrue($modelsManager->save($persona));

		//Check correct save
		$persona = $personasRepository->findFirst(
			[
				"estado='X'",
			]
		);
		$this->assertNotEquals($persona, false);
		$this->assertEquals($persona->nombres, 'LOST LOST');
		$this->assertEquals($persona->estado, 'X');

		//Update
		$persona->cupo = 150000;
		$persona->telefono = '123';
		$this->assertTrue($modelsManager->update($persona));

		//Checking correct update
		$persona = $personasRepository->findFirst(
			[
				"estado='X'",
			]
		);
		$this->assertNotEquals($persona, false);
		$this->assertEquals($persona->cupo, 150000);
		$this->assertEquals($persona->telefono, '123');

		$persona->assign(
			array(
				'nombres' => 'LOST UPDATE',
				'telefono' => '2121'
			)
		);

		//Update
		$this->assertTrue(
			$modelsManager->update($persona)
		);

		//Checking correct update
		$persona = $personasRepository->findFirst(
			array("estado='X'")
		);
		$this->assertNotEquals($persona, false);
		$this->assertEquals($persona->nombres, 'LOST UPDATE');
		$this->assertEquals($persona->telefono, '2121');

		//Create
		$persona = new Personas($di);
		$persona->cedula = 'CELL' . mt_rand(0, 999999);
		$persona->tipo_documento_id = 1;
		$persona->nombres = 'LOST CREATE';
		$persona->telefono = '1';
		$persona->cupo = 21000;
		$persona->estado = 'A';
		$this->assertTrue($modelsManager->create($persona));

		$persona = new Personas($di);
		$persona->assign(
			array(
				'cedula' => 'CELL' . mt_rand(0, 999999),
				'tipo_documento_id' => 1,
				'nombres' => 'LOST CREATE',
				'telefono' => '1',
				'cupo' => 21000,
				'estado' => 'A'
			)
		);
		$this->assertTrue(
			$modelsManager->create($persona)
		);

		//Grouping
		$difEstados = $peopleRepository->count(
			array("distinct" => "estado")
		);
		$this->assertEquals($difEstados, 3);

		$group = $peopleRepository->count(
			array("group" => "estado")
		);
		$this->assertEquals(count($group), 3);

		//Deleting
		$before = $peopleRepository->count();
		$this->assertTrue(
			$di->get("modelsManager")->delete($persona)
		);
		$this->assertEquals(
			$before - 1,
			$peopleRepository->count()
		);

		//Assign
		$persona = new Personas();

		$persona->assign(array(
			'tipo_documento_id' => 1,
			'nombres' => 'LOST CREATE',
			'telefono' => '1',
			'cupo' => 21000,
			'estado' => 'A',
			'notField' => 'SOME VALUE'
		));

		$expected = array(
			'cedula' => NULL,
			'tipo_documento_id' => 1,
			'nombres' => 'LOST CREATE',
			'telefono' => '1',
			'direccion' => NULL,
			'email' => NULL,
			'fecha_nacimiento' => NULL,
			'ciudad_id' => NULL,
			'creado_at' => NULL,
			'cupo' => 21000,
			'estado' => 'A',
		);

		$this->assertEquals($persona->toArray(), $expected);

		// Issue 1701
		$expected = array(
			'nombres' => 'LOST CREATE',
			'cupo' => 21000,
			'estado' => 'A',
		);
		$this->assertEquals($persona->toArray(array('nombres', 'cupo', 'estado')), $expected);

		//toArray with params must return only mapped fields if exists columnMap
		$persona = new Personers();
		$persona->assign(array(
			'slagBorgerId' => 1,
			'navnes' => 'LOST CREATE',
			'teletelefonfono' => '1',
			'kredit' => 21000,
			'status' => 'A',
			'notField' => 'SOME VALUE'
		));
		$expected = array(
			'navnes' => 'LOST CREATE',
			'kredit' => 21000,
			'status' => 'A',
		);
		$this->assertEquals($persona->toArray(array('nombres', 'cupo', 'estado')), array());//db fields names
		$this->assertEquals($persona->toArray(array('navnes', 'kredit', 'status')), $expected);//mapped fields names


		//Refresh
		$persona = $personasRepository->findFirst();

		$personaData = $persona->toArray();

		$persona->assign(array(
			'tipo_documento_id' => 1,
			'nombres' => 'LOST CREATE',
			'telefono' => '1',
			'cupo' => 21000,
			'estado' => 'A',
			'notField' => 'SOME VALUE'
		));

		$persona->refresh();
		$this->assertEquals($personaData, $persona->toArray());

		// Issue 1314
		$parts = new Parts2();
		$modelsManager->save($parts);

		// Issue 1506
		$persona = $personasRepository->findFirst(
			[
				'columns' => 'nombres, telefono, estado',
				"nombres = 'LOST CREATE'",
			]
		);
		$expected = array(
			'nombres'  => 'LOST CREATE',
			'telefono' => '1',
			'estado' => 'A'
		);

		$this->assertEquals($expected, $persona->toArray());
	}

	protected function _executeTestsRenamed($di)
	{

		$this->_prepareDb($di->getShared('db'));

		$modelsManager = $di->getShared('modelsManager');

		$personersRepository = $di->get("modelsManager")->getRepository(
			Personers::class
		);

		$params = array();
		$this->assertTrue(
			$personersRepository->count($params) > 0
		);

		$params = array("status = 'I'");
		$this->assertTrue(
			$personersRepository->count($params) > 0
		);

		$params = array("conditions" => "status='I'");
		$this->assertTrue(
			$personersRepository->count($params) > 0
		);

		//Find first
		$personer = $personersRepository->findFirst();
		$this->assertTrue(is_object($personer));
		$this->assertEquals(get_class($personer), 'Personers');
		$this->assertTrue(isset($personer->navnes));
		$this->assertTrue(isset($personer->status));

		$personer = $personersRepository->findFirst(
			[
				"status='I'",
			]
		);
		$this->assertTrue(is_object($personer));
		$this->assertTrue(isset($personer->navnes));
		$this->assertTrue(isset($personer->status));

		$personer = $personersRepository->findFirst(
			[
				"status='I'",
			]
		);
		$this->assertTrue(is_object($personer));
		$this->assertTrue(isset($personer->navnes));
		$this->assertTrue(isset($personer->status));

		$params = array("conditions" => "status='I'");
		$personer = $personersRepository->findFirst($params);
		$this->assertTrue(is_object($personer));
		$this->assertTrue(isset($personer->navnes));
		$this->assertTrue(isset($personer->status));

		$params = array("conditions" => "status='A'", "order" => "navnes");
		$personer = $personersRepository->findFirst($params);
		$this->assertTrue(is_object($personer));
		$this->assertTrue(isset($personer->navnes));
		$this->assertTrue(isset($personer->status));

		$params = array("status='A'", "order" => "navnes DESC", "limit" => 30);
		$personer = $personersRepository->findFirst($params);
		$this->assertTrue(is_object($personer));
		$this->assertTrue(isset($personer->navnes));
		$this->assertTrue(isset($personer->status));

		$params = array("status=?1", "bind" => array(1 => 'A'), "order" => "navnes DESC", "limit" => 30);
		$personer = $personersRepository->findFirst($params);
		$this->assertTrue(is_object($personer));
		$this->assertTrue(isset($personer->navnes));
		$this->assertTrue(isset($personer->status));

		$params = array("status=:status:", "bind" => array("status" => 'A'), "order" => "navnes DESC", "limit" => 30);
		$personer = $personersRepository->findFirst($params);
		$this->assertTrue(is_object($personer));
		$this->assertTrue(isset($personer->navnes));
		$this->assertTrue(isset($personer->status));

		$robottersRepository = $di->get("modelsManager")->getRepository(
			Robotters::class
		);

		$robotter = $robottersRepository->findFirst(
			array(
				1,
			)
		);
		$this->assertEquals(get_class($robotter), 'Robotters');

		//Find tests
		$personers = $personersRepository->find();
		$this->assertTrue(count($personers) > 0);

		$personers = $personersRepository->find(
			[
				"status='I'",
			]
		);
		$this->assertTrue(count($personers) > 0);

		$personers = $personersRepository->find(
			[
				"status='I'",
			]
		);
		$this->assertTrue(count($personers) > 0);

		$personers = $personersRepository->find(array("status='I'", "order" => "navnes"));
		$this->assertTrue(count($personers) > 0);

		$params = array("status='I'", "order" => "navnes", "limit" => 100);
		$personers = $personersRepository->find($params);
		$this->assertTrue(count($personers) > 0);

		$params = array("status=?1", "bind" => array(1 => "A"), "order" => "navnes", "limit" => 100);
		$personers = $personersRepository->find($params);
		$this->assertTrue(count($personers) > 0);

		$params = array("status=:status:", "bind" => array('status' => "A"), "order" => "navnes", "limit" => 100);
		$personers = $personersRepository->find($params);
		$this->assertTrue(count($personers) > 0);

		//Traverse the cursor
		$number = 0;

		$personers = $personersRepository->find(
			array(
				"conditions" => "status='A'",
				"order" => "navnes",
				"limit" => 20
			)
		);
		foreach($personers as $personer){
			$number++;
		}
		$this->assertEquals($number, 20);

		$personer = new Personers($di);
		$personer->borgerId = 'CELL'.mt_rand(0, 999999);
		$this->assertFalse($modelsManager->save($personer));

		//Messages
		$this->assertEquals(count($personer->getMessages()), 3);

		$messages = array(
			0 => ModelMessage::__set_state(array(
				'_type' => 'PresenceOf',
				'_message' => 'slagBorgerId is required',
				'_field' => 'slagBorgerId',
				'_code' => 0,
			)),
			1 => ModelMessage::__set_state(array(
				'_type' => 'PresenceOf',
				'_message' => 'kredit is required',
				'_field' => 'kredit',
				'_code' => 0,
			)),
			2 => ModelMessage::__set_state(array(
				'_type' => 'PresenceOf',
				'_message' => 'status is required',
				'_field' => 'status',
				'_code' => 0,
			)),
		);
		$this->assertEquals($personer->getMessages(), $messages);

		//Save
		$personer = new Personers($di);
		$personer->borgerId = 'CELL'.mt_rand(0, 999999);
		$personer->slagBorgerId = 1;
		$personer->navnes = 'LOST';
		$personer->telefon = '1';
		$personer->kredit = 20000;
		$personer->status = 'A';
		$this->assertTrue($modelsManager->save($personer));

		$personer = new Personers($di);
		$personer->borgerId = 'CELL'.mt_rand(0, 999999);
		$personer->slagBorgerId = 1;
		$personer->navnes = 'LOST LOST';
		$personer->telefon = '2';
		$personer->kredit = 0;
		$personer->status = 'X';
		$this->assertTrue($modelsManager->save($personer));

		//Check correct save
		$personer = $personersRepository->findFirst(
			[
				"status='X'",
			]
		);
		$this->assertNotEquals($personer, false);
		$this->assertEquals($personer->navnes, 'LOST LOST');
		$this->assertEquals($personer->status, 'X');

		//Update
		$personer->kredit = 150000;
		$personer->telefon = '123';
		$this->assertTrue($modelsManager->update($personer));

		//Checking correct update
		$personer = $personersRepository->findFirst(
			array("status='X'")
		);
		$this->assertNotEquals($personer, false);
		$this->assertEquals($personer->kredit, 150000);
		$this->assertEquals($personer->telefon, '123');

		//Update
		$personer->assign(
			array(
				'navnes' => 'LOST UPDATE',
				'telefon' => '2121'
			)
		);
		$this->assertTrue(
			$di->get("modelsManager")->update($personer)
		);

		//Checking correct update
		$personer = $personersRepository->findFirst(
			array("status='X'")
		);
		$this->assertNotEquals($personer, false);
		$this->assertEquals($personer->navnes, 'LOST UPDATE');
		$this->assertEquals($personer->telefon, '2121');

		//Create
		$personer = new Personers($di);
		$personer->borgerId = 'CELL'.mt_rand(0, 999999);
		$personer->slagBorgerId = 1;
		$personer->navnes = 'LOST CREATE';
		$personer->telefon = '2';
		$personer->kredit = 21000;
		$personer->status = 'A';
		$this->assertTrue($modelsManager->save($personer));

		$personer = new Personers($di);
		$personer->assign(
			array(
				'borgerId' => 'CELL'.mt_rand(0, 999999),
				'slagBorgerId' => 1,
				'navnes' => 'LOST CREATE',
				'telefon' => '1',
				'kredit' => 21000,
				'status' => 'A'
			)
		);
		$this->assertTrue(
			$modelsManager->create($personer)
		);

		//Deleting
		$before = $personersRepository->count();
		$this->assertTrue(
			$di->get("modelsManager")->delete($personer)
		);
		$this->assertEquals(
			$before - 1,
			$personersRepository->count()
		);

		//Assign
		$personer = new Personers();

		$personer->assign(array(
			'slagBorgerId' => 1,
			'navnes' => 'LOST CREATE',
			'telefon' => '1',
			'kredit' => 21000,
			'status' => 'A'
		));

		$expected = array(
			'borgerId' => NULL,
			'slagBorgerId' => 1,
			'navnes' => 'LOST CREATE',
			'telefon' => '1',
			'adresse' => NULL,
			'elektroniskPost' => NULL,
			'fodtDato' => NULL,
			'fodebyId' => NULL,
			'skabtPa' => NULL,
			'kredit' => 21000,
			'status' => 'A',
		);
		$this->assertEquals($personer->toArray(), $expected);

		//Refresh
		$personer = $personersRepository->findFirst();
		$personerData = $personer->toArray();

		$personer->assign(array(
			'slagBorgerId' => 1,
			'navnes' => 'LOST CREATE',
			'telefon' => '1',
			'kredit' => 21000,
			'status' => 'A'
		));

		$personer->refresh();
		$this->assertEquals($personerData, $personer->toArray());
	}
}
