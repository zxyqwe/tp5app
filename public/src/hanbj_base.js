window.mem_code = function (value) {
    switch (value) {
        case '-1':
            return '空号';
        case '0':
            return '实名会员';
        case '1':
            return '注销';
        case '2':
            return '停机保号';
        case '3':
            return '临时抢号';
        case '4':
            return '会员';
        default:
            return value;
    }
};
window.wx_mem_code = function (value) {
    switch (value) {
        case '0':
        case '4':
            return '<p>状态：' + window.mem_code(value);
        default:
            return '<p class="temp-text">状态：' + window.mem_code(value);
    }
};
window.grade = function (n) {
    switch (n) {
        case '0':
            return '会长';
        case '1':
            return '副会长';
        case '2':
            return '部长';
        case '3':
            return '副部长';
        case '4':
            return '干事';
        case '5':
            return '助理';
        case '6':
            return '专员';
        case '7':
            return '秘书长';
        case '8':
            return '副秘书长';
        case '9':
            return '名誉会长';
        case '10':
            return '代理部长';
        case '11':
            return '离职';
    }
};
window.fame_img = function (n) {
    switch (n) {
        case '0':
            return '/static/arrow-up.png';
        case '1':
            return '/static/arrow-up.png';
        case '2':
            return '/static/arrow-up.png';
        case '3':
            return '/static/arrow-up.png';
        case '4':
            return '/static/arrow-up.png';
        case '5':
            return '/static/arrow-up.png';
        case '6':
            return '/static/arrow-up.png';
        case '7':
            return '/static/arrow-up.png';
        case '8':
            return '/static/arrow-up.png';
        case '9':
            return '/static/arrow-up.png';
        case '10':
            return '/static/arrow-up.png';
        case '11':
            return '/static/arrow-up.png';
    }
};
window.repeat_icon = function (target, n) {
    var s = target, total = "";
    while (n > 0) {
        if (n % 2 === 1) {
            total += s;
        }
        if (n === 1) {
            break;
        }

        s += s;
        n = n >> 1;//相当于将n除以2取其商，或者说是开2次方
    }
    return total;
};
window.department = ['中枢', '会员部', '会员中心', '外联部', '外事中心', '外事部', '宣传部', '宣传中心',
    '活动部', '活动中心', '人力部', '人力资源与会员事务部', '秘书处', '办公室', '产业中心', '社推部',
    '新媒体运营部', '交流联络部', '公共关系部', '换届选举监委会'].sort();