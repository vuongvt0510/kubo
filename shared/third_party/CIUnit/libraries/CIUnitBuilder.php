<?php

/**
 * データ生成基底クラス
 *
 * @author Yoshikazu Ozawa
 */
class CIUnitBuilder
{
    /**
     * テストケースクラス
     * @var object
     */
    protected $base = NULL;

    public $fixtures = array();

    public function __construct(& $base)
    {
        $this->base =& $base;

        // fixtureの読み込み
        foreach ($this->fixtures as $fixt) {
            $fixt_name = $fixt . '_fixt';

            if (file_exists(TESTSPATH . 'fixtures/' . $fixt . '_fixt.yml')) {
                $this->$fixt_name = CIUnit::$spyc->loadFile(TESTSPATH . 'fixtures/' . $fixt . '_fixt.yml');
            } else {
                die('The file '. TESTSPATH . 'fixtures/' . $fixt . '_fixt.yml doesn\'t exist.');
            }
        }
    }

    public function __get($name)
    {
        return $this->base->{$name};
    }
}

