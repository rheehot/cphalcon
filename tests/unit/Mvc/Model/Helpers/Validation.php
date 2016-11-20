<?php

namespace Phalcon\Test\Unit\Mvc\Model\Helpers;

use Phalcon\Di;
use UnitTester;
use Phalcon\Db\RawValue;
use Phalcon\Mvc\Model\Message;
use Phalcon\Test\Models\Validation\Subscriptores;

class Validation
{
    protected function success(UnitTester $I)
    {
        /** @var \Phalcon\Di\FactoryDefault $di */
        $di = Di::getDefault();

        /** @var \Phalcon\Db\Adapter\Pdo\Mysql $connection */
        $connection = $di->getShared('db');

        $modelsManager = $di->getShared('modelsManager');

        $I->assertTrue($connection->delete('subscriptores'));

        $model = new Subscriptores();
        $model->assign(
            [
                'email'      => 'fuego@hotmail.com',
                'created_at' => new RawValue('now()'),
                'status'     => 'A'
            ]
        );
        $I->assertTrue(
            $modelsManager->save($model)
        );
    }

    protected function presenceOf(UnitTester $I)
    {
        /** @var \Phalcon\Di\FactoryDefault $di */
        $di = Di::getDefault();

        $modelsManager = $di->getShared('modelsManager');

        $model = new Subscriptores();
        $model->assign(
            [
                'email'      => 'diego@hotmail.com',
                'created_at' => null,
                'status'     => 'A'
            ]
        );
        $I->assertFalse(
            $modelsManager->save($model)
        );

        $expected = [
            Message::__set_state([
                '_message' => 'Field created_at is required',
                '_field'   => 'created_at',
                '_type'    => 'PresenceOf',
                '_code'    => 0,
            ])
        ];

        $I->assertEquals($expected, $model->getMessages());
    }

    protected function email(UnitTester $I)
    {
        /** @var \Phalcon\Di\FactoryDefault $di */
        $di = Di::getDefault();

        $modelsManager = $di->getShared('modelsManager');

        $model = new Subscriptores();
        $model->assign(
            [
                'email'      => 'fuego?=',
                'created_at' => new RawValue('now()'),
                'status'     => 'A'
            ]
        );
        $I->assertFalse(
            $modelsManager->save($model)
        );

        $expected = [
            Message::__set_state([
                '_message' => 'Field email must be an email address',
                '_field'   => 'email',
                '_type'    => 'Email',
                '_code'    => 0,
            ])
        ];

        $I->assertEquals($expected, $model->getMessages());
    }

    /**
     * @issue 1243
     * @param UnitTester $I
     */
    protected function emailWithDot(UnitTester $I)
    {
        /** @var \Phalcon\Di\FactoryDefault $di */
        $di = Di::getDefault();

        $modelsManager = $di->getShared('modelsManager');

        $model = new Subscriptores();
        $model->assign(
            [
                'email'      => 'serghei.@yahoo.com',
                'created_at' => new RawValue('now()'),
                'status'     => 'A'
            ]
        );
        $I->assertFalse(
            $modelsManager->save($model)
        );

        $expected = [
            Message::__set_state([
                '_message' => 'Field email must be an email address',
                '_field'   => 'email',
                '_type'    => 'Email',
                '_code'    => 0,
            ])
        ];

        $I->assertEquals($expected, $model->getMessages());
    }

    protected function exclusionIn(UnitTester $I)
    {
        /** @var \Phalcon\Di\FactoryDefault $di */
        $di = Di::getDefault();

        $modelsManager = $di->getShared('modelsManager');

        $model = new Subscriptores();
        $model->assign(
            [
                'email'      => 'serghei@hotmail.com',
                'created_at' => new RawValue('now()'),
                'status'     => 'P'
            ]
        );
        $I->assertFalse(
            $modelsManager->save($model),
            'The ExclusionIn Validation failed'
        );

        $expected = [
            Message::__set_state([
                '_message' => 'Field status must not be a part of list: P, I, w',
                '_field'   => 'status',
                '_type'    => 'ExclusionIn',
                '_code'    => 0,
            ]),
            Message::__set_state([
                '_message' => 'Field status must be a part of list: A, y, Z',
                '_field'   => 'status',
                '_type'    => 'InclusionIn',
                '_code'    => 0,
            ]),
        ];

        $I->assertEquals($expected, $model->getMessages());
    }

    protected function inclusionIn(UnitTester $I)
    {
        /** @var \Phalcon\Di\FactoryDefault $di */
        $di = Di::getDefault();

        $modelsManager = $di->getShared('modelsManager');

        $model = new Subscriptores();
        $model->assign(
            [
                'email'      => 'serghei@hotmail.com',
                'created_at' => new RawValue('now()'),
                'status'     => 'R'
            ]
        );
        $I->assertFalse(
            $modelsManager->save($model)
        );

        $expected = [
            Message::__set_state([
                '_message' => 'Field status must be a part of list: A, y, Z',
                '_field'   => 'status',
                '_type'    => 'InclusionIn',
                '_code'    => 0,
            ]),
        ];

        $I->assertEquals($expected, $model->getMessages());
    }

    protected function uniqueness1(UnitTester $I)
    {
        /** @var \Phalcon\Di\FactoryDefault $di */
        $di = Di::getDefault();

        $modelsManager = $di->getShared('modelsManager');

        $data = [
            'email'      => 'jurigag@hotmail.com',
            'created_at' => new RawValue('now()'),
            'status'     => 'A'
        ];

        $model = new Subscriptores();
        $model->assign($data);
        $I->assertTrue($modelsManager->save($model));

        $model = new Subscriptores();
        $model->assign($data);
        $I->assertFalse($modelsManager->save($model));

        $expected = [
            Message::__set_state([
                '_message' => 'Field email must be unique',
                '_field'   => 'email',
                '_type'    => 'Uniqueness',
                '_code'    => 0,
            ]),
        ];

        $I->assertEquals($expected, $model->getMessages());
    }

    /**
     * @issue 1527
     * @param UnitTester $I
     */
    protected function uniqueness2(UnitTester $I)
    {
        /** @var \Phalcon\Di\FactoryDefault $di */
        $di = Di::getDefault();

        $modelsManager = $di->getShared('modelsManager');

        $subscriptoresRepository = $modelsManager->getRepository(
            Subscriptores::class
        );

        $model = $subscriptoresRepository->findFirst();
        $model->assign($model->toArray());
        $modelsManager->save($model);

        $I->assertTrue($model->validation());
        $I->assertEmpty($model->getMessages());
    }

    protected function regex(UnitTester $I)
    {
        /** @var \Phalcon\Di\FactoryDefault $di */
        $di = Di::getDefault();

        $modelsManager = $di->getShared('modelsManager');

        $model = new Subscriptores();
        $model->assign(
            [
                'email'      => 'andres@hotmail.com',
                'created_at' => new RawValue('now()'),
                'status'     => 'y'
            ]
        );
        $I->assertFalse(
            $modelsManager->save($model)
        );

        $expected = [
            Message::__set_state([
                '_message' => 'Field status does not match the required format',
                '_field'   => 'status',
                '_type'    => 'Regex',
                '_code'    => 0,
            ]),
        ];

        $I->assertEquals($expected, $model->getMessages());
    }

    protected function tooLong(UnitTester $I)
    {
        /** @var \Phalcon\Di\FactoryDefault $di */
        $di = Di::getDefault();

        $modelsManager = $di->getShared('modelsManager');

        $model = new Subscriptores();
        $model->assign(
            [
                'email'      => str_repeat('a', 50) . '@hotmail.com',
                'created_at' => new RawValue('now()'),
                'status'     => 'A'
            ]
        );
        $I->assertFalse(
            $modelsManager->save($model)
        );

        $expected = [
            Message::__set_state([
                '_message' => 'Field email must not exceed 50 characters long',
                '_field'   => 'email',
                '_type'    => 'TooLong',
                '_code'    => 0,
            ]),
        ];

        $I->assertEquals($expected, $model->getMessages());
    }

    protected function tooShort(UnitTester $I)
    {
        /** @var \Phalcon\Di\FactoryDefault $di */
        $di = Di::getDefault();

        $modelsManager = $di->getShared('modelsManager');

        $model = new Subscriptores();
        $model->assign(
            [
                'email'      => 'a@b.c',
                'created_at' => new RawValue('now()'),
                'status'     => 'A'
            ]
        );
        $I->assertFalse(
            $modelsManager->save($model)
        );

        $expected = [
            Message::__set_state([
                '_message' => 'Field email must be at least 7 characters long',
                '_field'   => 'email',
                '_type'    => 'TooShort',
                '_code'    => 0,
            ]),
        ];

        $I->assertEquals($expected, $model->getMessages());
    }
}
