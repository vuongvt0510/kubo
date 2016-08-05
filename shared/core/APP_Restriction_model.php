<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

if ( ! class_exists('APP_Model')) {
    require_once dirname(__FILE__) . "/APP_Model.php";
}

/**
 * 単一テーブル継承モデル
 *
 * APP_Modelと異なりレコードアクセスにタイプによる制限を加える
 *
 * @package ExtendsCI\Model
 * @version $id$
 * @copyright 2013- Interest Marketing, inc. (CONTACT info@interest-marketing.net)
 * @author Yoshikazu Ozawa
 */
class APP_Restriction_model extends APP_Model {

    /**
     * 制限を加えるカラム名
     * @var string
     */
    public $sti_column_name = "type";

    /**
     * タイプ
     * @var string|int
     */
    public $type = NULL;

    /**
     * レコード取得
     *
     * 指定したプライマリキーのレコードを取得する。
     *
     * @access public
     * @return false|object レコード
     *
     * @internal param string $id プライマリキー
     * @internal param array $options オプション
     */
    public function find(/* polymorphic */)
    {
        $this->_set_type_condition();
        return call_user_func_array("parent::find", func_get_args());
    }

    /**
     * レコード全件取得
     *
     * 条件に一致したレコードを全件取得する。
     * 
     * @access public
     * @param array $options オプション
     * @return false|array レコード
     */
    public function all($options = array())
    {
        $this->_set_type_condition();
        return parent::all($options);
    }

    /**
     * レコード件数取得
     *
     * 条件に一致したレコードを件数取得する。
     *
     * @access public
     * @param array $options オプション
     * @return false|int 取得件数
     */
    public function count_rows($options = array())
    {
        $this->_set_type_condition();
        return parent::count_rows($options);
    }

    /**
     * レコード作成
     *
     * 指定された値のレコードを登録する。
     *
     * @access public
     * @param array $attributes 登録内容
     * @param array $options オプション
     * @return false|int|object 登録したプライマリキー または 登録した対象のレコード
     */
    public function create($attributes = array(), $options = array())
    {
        $this->_set_type_attribute($attributes);
        return parent::create($attributes, $options);
    }

    /**
     * 複数レコード作成
     *
     * 指定された配列のレコードをまとめて作成する
     *
     * @access public
     * @param array $array 作成するレコードのパラメータの配列
     * @return bool
     */
    public function bulk_create($array, $options = array())
    {
        foreach ($array as & $attributes) {
            $this->_set_type_attribute($attributes);
        }

        return parent::bulk_create($array, $options);
    }

    /**
     * レコード更新
     *
     * 指定されたIDのレコードを更新する。
     *
     * @access public
     * @return false|int|object 更新件数 または 再取得した更新対象のレコード
     *
     * @throws APP_Model_exception
     *
     * @internal param int $id プライマリキー
     * @internal param array $attributes 更新パラメータ
     */
    public function update(/* polymorphic */)
    {
        list($args, $attributes, $options) = $this->_parse_update_args(func_get_args());

        $this->_set_type_condition();
        $this->_set_type_attribute($attributes);

        array_push($args, $attributes, $options);

        return call_user_func_array('parent::update', $args);
    }

    /**
     * レコード全体更新
     *
     * 指定された条件に一致するレコード全件を更新する。
     * 論理削除したレコードは更新されない。ただし with_deleted オプションを付与すると更新できる。
     *
     * @access public
     * @param array $attributes 更新するレコードのパラメータ
     * @return false|int 更新件数
     */
    public function update_all($attributes = array(), $options = array())
    {
        $this->_set_type_condition();
        $this->_set_type_attribute($attributes);
        return parent::update_all($attributes, $options);
    }

    /**
     * レコード削除
     *
     * 指定されたプライマリキーのレコードを削除にする。
     *
     * @access public
     * @return false|int 削除件数
     *
     * @internal param int $id プライマリキー
     * @internal param array $options オプション
     */
    public function destroy(/* polymorphic */)
    {
        $this->_set_type_condition();
        return call_user_func_array('parent::destroy', func_get_args());
    }

    /**
     * レコード全件論理削除
     *
     * 指定された条件のレコードを削除にする。
     *
     * @access public
     * @param array $options オプション
     * @return false|int 削除件数
     */
    public function destroy_all($options = array())
    {
        $this->_set_type_condition();
        return parent::destroy_all($options);
    }

    /**
     * テーブル内レコード全消去
     *
     * 登録されているレコードを全消去する。
     *
     * @access public
     * @return false|int 削除件数
     */
    public function empty_table()
    {
        if (isset($this->type)) {
            return $this->destroy_all();
        } else {
            return parent::empty_table();
        }
    }

    /**
     * アクセス条件追加
     *
     * 各種メソッドが呼ばれる際に、付与されるアクセス条件を定義する
     *
     * @access protected
     * @return void
     */
    protected function _set_type_condition()
    {
        if (! empty($this->type)) {
            if (is_array($this->type)) {
                $this->where_in($this->column_name($this->sti_column_name), $this->type);
            } else {
                $this->where($this->column_name($this->sti_column_name), $this->type);
            }
        }
    }

    /**
     * 登録内容追加
     *
     * 登録メソッドが呼ばれる際に、登録内容に付与される内容を定義する
     *
     * @access protected
     * @param array $attributes 登録内容
     * @return void
     */
    protected function _set_type_attribute(& $attributes)
    {
        if (! empty($this->type)) {
            $attributes[$this->sti_column_name] = $this->type;
        }
    }

}
