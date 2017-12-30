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
            return '<p>状态：'+window.mem_code(value);
        default:
            return '<p class="temp-text">状态：'+window.mem_code(value);
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
    }
};