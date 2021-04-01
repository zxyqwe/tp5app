# -*- coding:utf-8 -*-
import sys
reload(sys)
sys.setdefaultencoding('utf-8')
import requests
import json

data = {
    "card": {
        "card_type": "MEMBER_CARD",
        "member_card": {
            "background_pic_url": "http://mmbiz.qpic.cn/mmbiz_jpg/PP2q8S7QAJel0W2MjUQgaA4tiaQuK4kkoToThoHkPumpNHvicf19vPMLMBLGY6VnDmgMWMwmck48hR1ib8EOjO6LQ/0",
            # http://mmbiz.qpic.cn/mmbiz_jpg/PP2q8S7QAJel0W2MjUQgaA4tiaQuK4kkofPlwxRT8kiaUQq7p3Wtic2TT5RBD6PEia80KT5x144DlvZmYkPsb4vUwg/0
            # http://mmbiz.qpic.cn/mmbiz_jpg/PP2q8S7QAJdbFvGfSCOEia4ddWiabfVkXtblCEBKOcyBTmT7EUPQBaUE28FD8paOEYwIyFtWBzsWU8paviaEWVxYQ/0
            # http://mmbiz.qpic.cn/mmbiz_jpg/PP2q8S7QAJdbFvGfSCOEia4ddWiabfVkXtAQRbaCCWEefd6eAzc808USkpNMfaSygZLj1yndXZc9MbD6JWa0nbKw/0
            "base_info": {
                "logo_url": "http://mmbiz.qpic.cn/mmbiz_jpg/PP2q8S7QAJcuGbV0gs8GC1jHSYJ7xWgkpoN9T5icr1DwmTeTicXgfTibjYiazmJLv8MAUBHXZLXtSicopribicOT8mYNw/0",
                "brand_name": "北京汉服协会",
                "code_type": "CODE_TYPE_QRCODE",
                "title": "会员卡",
                "color": "Color010",
                "description":
                "1、会员卡一经售出，不退不换，请谨慎决策。\n2、购买会员卡默认已了解并承诺遵守会员须知中的有关规定。\n3、积分有效期为2年。\n4、汉服北京有权对会员福利做出适当调整，不另行通知。",
                "date_info": {
                    "type": "DATE_TYPE_PERMANENT",
                },
                "sku": {
                    "quantity": 10000000
                },
                "get_limit": 1,
                "use_custom_code": False,
                "can_give_friend": False,
                "custom_url_name": "活动报名",
                "custom_url": "https://app.zxyqwe.com/hanbj/mobile/#activity",
                "custom_url_sub_title": "更多惊喜",
                "promotion_url_name": "会员特权",
                "promotion_url": "https://app.zxyqwe.com/hanbj/mobile/#promotion",
                "promotion_url_sub_title": "福利多多"
            },
            "advanced_info": {
                "text_image_list": [
                    {
                        "image_url": "http://mmbiz.qpic.cn/mmbiz/p98FjXy8LacgHxp3sJ3vn97bGLz0ib0Sfz1bjiaoOYA027iasqSG0sjpiby4vce3AtaPu6cIhBHkt6IjlkY9YnDsfw/0",
                        "text":
                        "1、会员须遵守国家法律，遵守汉服北京的各项章程、规定，认同本群体的原则理念和基本共识，履行吧务组决议，不公开发表过激或极端言论，不做有损汉服群体整体利益和对外形象的行为。因违法违纪、违反汉服北京规章制度等行为造成恶劣影响，可由汉服北京管理团队讨论后，终止或撤销其会员资格，所缴纳会费恕不退回。\n2、会员在行使任何权利时，都应携带并配合出示有效会员凭证，否则将不享有会员权利。\n3、会员可主动申请终止或撤销会员资格，自汉服北京管理团队确认之日起会员资格失效，所缴纳会费恕不退回；再次申请加入会员时，重新计算会员时效。"
                    }
                ]
            },
            "supply_bonus": True,
            "bonus_url": "https://app.zxyqwe.com/hanbj/mobile/#bonus",
            "supply_balance": False,
            "prerogative":
            "1、公开活动费用减免。吧务组组织的面向大众的活动，包括但不限于传统节日大活动、雅集类活动等。\n2、特别活动优先参与。对于部分限定活动享有优先报名特权。\n3、汉北周边购买最高折扣。尊享汉北周边产品最高折扣价。汉服北京承诺同一时间在任何渠道所售周边产品价格不低于会员折扣价。\n4、汉服租借及礼仪类服务折扣。可以优惠价租借汉服，预定成人礼抓周礼等礼仪类服务享受专属折扣。（限指定公司）\n5、汉北合作商家优惠。在指定汉服、饰品、国货、汉婚及其他商家购买产品和服务尊享汉北会员折扣。\n6、会员积分。参与活动可获得积分，达到一定数额可升级高级会员或兑换其他福利。\n7、参与限量会员牌订制。\n8、其他不定期其他特殊优惠。",
            "auto_activate": False,
            "activate_url": "https://app.zxyqwe.com/hanbj/mobile",
            "custom_field1": {
                "name": "会员号",
                "url": "https://app.zxyqwe.com/hanbj/mobile",
            },
            "custom_field2": {
                "name": "缴费",
                "url": "https://app.zxyqwe.com/hanbj/mobile/#valid",
            },
            "custom_cell1": {
                "name": "未完成",
                "tips": "显示未完成",
                "url": "https://app.zxyqwe.com/hanbj/mobile/#custom",
            }
        }
    }

}
access = '10_w6yX3aT0SlpAXp0KUAtvHOujLueVW5z4CIAGhL-wn-8IxpPNJESqc52fwpfOZ27brP-Vk1Vgmw6xvGaXgkPUtXdAwA_MKZyphQaC--cFBpXmrRGGczuzhK2KhHHN4XODRSA3rMZki4ROkq9IGLEjAHAZEX'
# https://api.weixin.qq.com/card/update?access_token=
# https://api.weixin.qq.com/card/modifystock?access_token=
# https://api.weixin.qq.com/cgi-bin/menu/create?access_token=
url = 'https://api.weixin.qq.com/cgi-bin/menu/create?access_token=' + access
cid = 'pJkBfv06_x38RSmlhoR-2X7rWsxw'
datab = {
    "button": [
        {
            "name": "我的",
            "sub_button": [
	        {
	            "type": "view",
	            "name": "个人中心",
	            "url": "https://app.zxyqwe.com/hanbj/mobile"
	        },
	        {
	            "type": "view",
	            "name": "帮助说明",
	            "url": "https://app.zxyqwe.com/hanbj/mobile/help"
	        }
            ]
        },
        {
            "name": "公告栏",
            "sub_button": [
                {
                    "type": "view",
                    "name": "会费公告",
                    "url": "https://app.zxyqwe.com/hanbj/pub/bulletin"
                },
                {
                    "type": "view",
                    "name": "名人堂",
                    "url": "https://app.zxyqwe.com/hanbj/pub/fame"
                },
                {
                    "type": "view",
                    "name": "积分排行",
                    "url": "https://app.zxyqwe.com/hanbj/pub/bonus"
                }
            ]
        },
{
"name":"微店",
"type": "view",
"url": "https://weidian.com/?userid=1353579309"
}
    ]
}
mem = {"base_info": {
                "promotion_url_name": "商家优惠",
                "promotion_url": "https://app.zxyqwe.com/hanbj/mobile/#prom",},
"advanced_info": {
                "text_image_list": [
                    {
                        "image_url": "http://mmbiz.qpic.cn/mmbiz_jpg/PP2q8S7QAJdkZWIvIQheoscaVOPSibtibZQUqibrjNzZoIqo5mLeib1RhUe8vvjhf9LWdBhX4qV1mc7iaKInIYlWTqg/0",
                         "text":
                        "1、会员须遵守国家法律，遵守汉服北京的各项章程、规定，认同本群体的原则理念和基本共识，履行吧务组决议，不公开发表过激或极端言论，不做有损汉服群体整体利益和对外形象的行为。因违法违纪、违反汉服北京规章制度等行为造成恶劣影响，可由汉服北京管理团队讨论后，终止或撤销其会员资格，所缴纳会费恕不退回。\n2、会员在行使任何权利时，都应携带并配合出示有效会员凭证，否则将不享有会员权利。\n3、会员可主动申请终止或撤销会员资格，自汉服北京管理团队确认之日起会员资格失效，所缴纳会费恕不退回；再次申请加入会员时，重新计算会员时效。"
                   
            }]},"custom_cell1": {
                "name": "汉北会员",
                "tips": "攻略指南",
                "url": "https://app.zxyqwe.com/hanbj/mobile/help",
            }
}
data = {
    "card_id": cid,
    #"increase_stock_value": 10000000
    "member_card": mem
}
res = requests.post(url, data=json.dumps(datab, ensure_ascii=False))
res.raise_for_status()
print res.text
