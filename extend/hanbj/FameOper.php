<?php

namespace hanbj;

use PDOStatement;
use think\Collection;
use think\Db;
use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\Exception;
use think\exception\DbException;
use think\exception\HttpResponseException;
use think\exception\PDOException;
use util\MysqlLog;

class FameOper
{
    const chairman = 0;//会长
    const vice_chairman = 1;//副会长
    const manager = 2;//部长
    const vice_manager = 3;//副部长
    const member = 4;//干事
    const assistant = 5;//助理
    const commissioner = 6;//专员
    const secretary = 7;//秘书长
    const vice_secretary = 8;//副秘书长
    const fame_chair = 9;//名誉会长
    const like_manager = 10;//代理部长
    const leave = 11;//撤销记录
    const fixed_vice_chairman = 12;//专职副会长
    const intern = 13;//实习
    const adviser = 14;//顾问
    const manager_secretary = 15;//部门秘书
    const council_president = 16;//理事长
    const council_director = 17;//理事
    const max_pos = 17;
    const order = [
        self::chairman,
        self::council_president,
        self::fame_chair,
        self::fixed_vice_chairman,
        self::vice_chairman,
        self::secretary,
        self::manager,
        self::like_manager,
        self::vice_secretary,
        self::vice_manager,
        self::adviser,
        self::commissioner,
        self::council_director,
        self::manager_secretary,
        self::assistant,
        self::member,
        self::intern,
        self::leave
    ];

    public static function translate($type)
    {
        if ($type === null) {
            return "非理事会成员";
        }
        switch ($type) {
            case self::chairman:
                return "会长";
            case self::vice_chairman:
                return "副会长";
            case self::manager:
                return "部长";
            case self::vice_manager:
                return "副部长";
            case self::member:
                return "干事";
            case self::assistant:
                return "助理";
            case self::commissioner:
                return "专员";
            case self::secretary:
                return "秘书长";
            case self::vice_secretary:
                return "副秘书长";
            case self::fame_chair:
                return "名誉会长";
            case self::like_manager:
                return "代理部长";
            case self::leave:
                return "撤销记录";
            case self::fixed_vice_chairman:
                return "专职副会长";
            case self::intern:
                return "实习";
            case self::adviser:
                return "顾问";
            case self::manager_secretary:
                return "部门秘书";
            case self::council_president:
                return "理事长";
            case self::council_director:
                return "理事";
            default:
                return "未知：$type";
        }
    }

    /**
     * @return array
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public static function getUp()//会长层、部长
    {
        return self::get([
            self::chairman,
            self::vice_chairman,
            self::fixed_vice_chairman,
            self::secretary,
            self::manager
        ]);
    }

    /**
     * @return array
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public static function getTop()//会长层
    {
        return self::get([
            self::chairman,
            self::vice_chairman,
            self::fixed_vice_chairman
        ]);
    }

    /**
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public static function getWhoCanLogIn()
    {
        // uniq in current fame
        return self::get([
            self::chairman,
            self::vice_chairman,
            self::fixed_vice_chairman,
            self::manager,
            self::vice_manager,
            self::adviser,
            self::commissioner,
            self::manager_secretary,
            self::secretary,
            self::vice_secretary,
            self::like_manager
        ]);
    }

    /**
     * @param $group
     * @return false|PDOStatement|string|Collection
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public static function get_label_for_test($group)
    {
        $map['year'] = HBConfig::YEAR;
        $map['grade'] = ['in', $group];
        $map['type'] = 0;
        return Db::table('fame')
            ->where($map)
            ->field([
                'unique_name as u',
                'label as l',
                'grade as g'
            ])
            ->cache(600)
            ->select();
    }

    /**
     * @param $group
     * @return array
     * @throws DataNotFoundException
     * @throws ModelNotFoundException
     * @throws DbException
     */
    public static function get_for_test($group)
    {
        $map['year'] = HBConfig::YEAR;
        $map['grade'] = ['in', $group];
        $map['label'] = ['not in', ['换届选举监委会']];
        $map['type'] = 0;
        return self::get_inner($map);
    }

    /**
     * @param $group
     * @return array
     * @throws DataNotFoundException
     * @throws ModelNotFoundException
     * @throws DbException
     */
    public static function get($group)
    {
        $map['year'] = HBConfig::YEAR;
        $map['grade'] = ['in', $group];
        return self::get_inner($map);
    }

    /**
     * @param $map
     * @return array
     * @throws DataNotFoundException
     * @throws ModelNotFoundException
     * @throws DbException
     */
    private static function get_inner($map)
    {
        $ret = Db::table('fame')
            ->where($map)
            ->field('unique_name as u')
            ->cache(600)
            ->select();
        $data = [];
        foreach ($ret as $i) {
            $data[] = $i['u'];
        }
        return $data;
    }

    private static function label_sort($label)
    {
        if(in_array($label, ['中枢', '理事会'])) {
            return '0' + $label;
        }
        if(in_array($label, ['执行干事'])) {
            return '9' + $label;
        }
        return '1' + $label;
    }

    public static function cmp($a, $b)
    {
        $order = array_flip(self::order);
        //year desc,grade asc,label asc
        if ($a['y'] !== $b['y']) {
            return $a['y'] < $b['y'] ? 1 : -1;
        }
        if ($a['label'] !== $b['label']) {
            return self::label_sort($a['label']) < self::label_sort($b['label']) ? -1 : 1;
        }
        if ($a['grade'] !== $b['grade']) {
            return $order[$a['grade']] < $order[$b['grade']] ? -1 : 1;
        }
        return 0;
    }

    public static function sort($ret)
    {
        usort($ret, [self::class, 'cmp']);
        return $ret;
    }

    /**
     * @param $uname
     * @throws Exception
     * @throws PDOException
     */
    public static function clear($uname)
    {
        $map['unique_name'] = $uname;
        $data['unique_name'] = $uname . date("Y-m-d H:i:s");
        $ret = Db::table('fame')
            ->where($map)
            ->update($data);
        if (intval($ret) !== 0) {
            trace("Fame Clear $uname $ret", MysqlLog::INFO);
        }
    }

    /**
     * @return array
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public static function getOrder()
    {
        $join = [
            ['member m', 'm.unique_name=f.unique_name', 'left']
        ];
        $res = Db::table('fame')
            ->alias('f')
            ->join($join)
            ->where(['grade' => ['neq', self::leave]])
            ->field([
                'f.unique_name',
                'tieba_id',
                'year as y',
                'grade',
                'label',
                'type'
            ])
            ->select();
        $res = self::sort($res);
        $data = [];
        foreach ($res as $item) {
            $year = $item['y'];
            if (!isset($data[$year])) {
                $data[$year] = ['name' => $year];
                $data[$year]['teams'] = [];
            }
            $team = $item['label'];
            if (!isset($data[$year]['teams'][$team])) {
                $data[$year]['teams'][$team] = ['name' => $team];
                $data[$year]['teams'][$team]['ms'] = [];
            }
            $data[$year]['teams'][$team]['ms'][] = [
                'u' => $item['unique_name'],
                't' => $item['tieba_id'],
                'id' => $item['grade'],
                'te' => $item['type']
            ];
        }
        $data = array_values($data);
        foreach ($data as &$item) {
            $item['teams'] = array_values($item['teams']);
        }
        return $data;
    }

    /**
     * @return false|PDOStatement|string|Collection
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public static function getCurrentLabel()
    {
        return Db::table('fame')
            ->where([
                'year' => HBConfig::YEAR
            ])
            ->field([
                'distinct label'
            ])
            ->select();
    }

    /**
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public static function cacheMyCurrentYear()
    {
        $ret = Db::table('fame')
            ->where([
                'unique_name' => session('unique_name'),
                'year' => HBConfig::YEAR,
                'grade' => ['neq', self::leave],
                'type' => 0
            ])
            ->field([
                'grade',
                'label'
            ])
            ->find();
        if (null === $ret) {
            session('fame', null);
            return;
        }
        session('fame', json_encode([
            'grade' => $ret['grade'],
            'label' => $ret['label']
        ]));
    }

    /**
     * @param $year
     * @param $grade
     * @param $label
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public static function assertEditRight($year, $grade, $label)
    {
        $unique_name = session('unique_name');
        if (UserOper::grantAllRight($unique_name)) {
            return;
        }
        if (intval($year) === HBConfig::YEAR) {
            $ret = session('fame');
            if (null !== $ret) {
                $ret = json_decode($ret, true);
                if ($label === $ret['label']) {
                    $order = array_flip(self::order);
                    if ($order[$ret['grade']] < $order[intval($grade)]) {
                        return;
                    }
                }
            }
        }
        $err = "编辑名人堂信息， $unique_name 没有权限编辑第 $year 届 $label 部门 $grade 级别的信息";
        trace($err, MysqlLog::ERROR);
        if (request()->isAjax()) {
            $res = json(['msg' => $err], 400);
        } else {
            $res = redirect('https://app.zxyqwe.com/hanbj/index/home');
        }
        throw new HttpResponseException($res);
    }

    /**
     * @param $unique
     * @return array|false
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public static function getFameForUnique($unique)
    {
        return Db::table('fame')
            ->where([
                'unique_name' => $unique,
                'year' => HBConfig::YEAR,
                'grade' => ['neq', self::leave],
                'type' => 0
            ])
            ->cache(600)
            ->field([
                'grade',
                'label'
            ])
            ->find();
    }

    /**
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public static function getMyHistory()
    {
        $map['unique_name'] = session('unique_name');
        $map['grade'] = ['neq', self::leave];
        return Db::table('fame')
            ->where($map)
            ->order('year desc')
            ->field([
                'year',
                'grade',
                'label',
                'type'
            ])
            ->select();
    }

    public static function insertInWhile(&$data)
    {
        Db::startTrans();
        try {
            $res = Db::table('fame')
                ->insertAll($data);
            if ($res === count($data)) {
                Db::commit();
                trace("Fame Add " . json_encode($data), MysqlLog::INFO);
                throw new HttpResponseException(json(['msg' => 'ok']));
            } else {
                Db::rollback();
                throw new HttpResponseException(json(['msg' => $res], 400));
            }
        } catch (Exception $e) {
            // Think Exception
            Db::rollback();
            $e = $e->getMessage();
            preg_match('/Duplicate entry \'(.*)-(.*)-(.*)\' for key \'year_uniq\'/', $e, $token);
            if (isset($token[3])) {
                trace("Fame Add $e", MysqlLog::ERROR);
                $e = "错误！【 {$token[2]} 】已经被登记在第【 {$token[1]} 】届吧务组【 {$token[3]} 】部门中了。请删除此项，重试。";
                throw new HttpResponseException(json(['msg' => '' . $e], 400));
            }
            preg_match('/Duplicate entry \'(.*)-(.*)-(.*)\' for key \'type_uniq\'/', $e, $token);
            if (isset($token[3])) {
                foreach ($data as &$idx) {
                    if ($idx['unique_name'] === $token[2]) {
                        $idx['type'] = null;
                    }
                }
                return;
            }
            throw new HttpResponseException(json(['msg' => 'Unknown ' . $e], 400));
        }
    }

    /**
     * @param $label
     * @param $grade
     * @param $type
     * @return array
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public static function getUnionId($label, $grade, $type)
    {
        $map = [
            'f.year' => HBConfig::YEAR,
            'i.status' => SubscribeOper::Subscribe
        ];
        if ('ALL' !== $label) {
            $map['f.label'] = $label;
        }
        if ('ALL' !== $grade) {
            $map['f.grade'] = intval($grade);
        } else {
            $map['f.grade'] = ['neq', self::leave];
        }
        if ('ALL' !== $type) {
            $map['f.type'] = intval($type);
            if ($map['f.type'] === 1) {
                $map['f.type'] = null;
            }
        }
        $join = [
            ['member m', 'm.unique_name=f.unique_name', 'left'],
            ['idmap i', 'm.openid=i.openid', 'left']
        ];
        $ret = Db::table('fame')
            ->alias('f')
            ->join($join)
            ->where($map)
            ->field(['distinct i.unionid'])
            ->cache(600)
            ->select();
        $data = [];
        foreach ($ret as $item) {
            $data[] = $item['unionid'];
        }
        return $data;
    }
}
/*
CREATE TABLE `fame` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `unique_name` varchar(45) NOT NULL,
  `year` tinyint(4) NOT NULL,
  `grade` tinyint(4) NOT NULL,
  `label` varchar(45) NOT NULL,
  `type` tinyint(4) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `year_uniq` (`year`,`unique_name`,`label`),
  UNIQUE KEY `type_uniq` (`year`,`unique_name`,`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
 */