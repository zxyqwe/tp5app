#!/usr/bin/env python3
# https://raw.githubusercontent.com/instapp/china-regions/master/data/json/regions.object.flat.full.json
import json

with open("./regions.object.flat.full.json") as f:
    data=json.load(f)

#print(len(data))
output_data={}

for idx in data.values():
    if 'district' not in idx:
        continue
    #print(idx)
    if idx['province'] not in output_data:
        output_data[idx['province']] = {'label':idx['province'],'value':idx['code'][:2],'children':{}}
    assert output_data[idx['province']]['value'] == idx['code'][:2] or print(output_data[idx['province']], idx)
    if idx['city'] == "重庆市":
        idx['code'] = idx['code'][:2] + '01' + idx['code'][4:6]
    if idx['city'] not in output_data[idx['province']]['children']:
        output_data[idx['province']]['children'][idx['city']] = {'label':idx['city'],'value':idx['code'][2:4],'children':{}}
    assert output_data[idx['province']]['children'][idx['city']]['value'] == idx['code'][2:4] or print(output_data[idx['province']]['children'][idx['city']], idx)
    if idx['district'] not in output_data[idx['province']]['children'][idx['city']]['children']:
        output_data[idx['province']]['children'][idx['city']]['children'][idx['district']] = {'label':idx['district'],'value':idx['code'][4:6]}
    else:
        assert False or print(output_data[idx['province']]['children'][idx['city']]['children'][idx['district']], idx)

output=list(output_data.values())
for idx in output:
    idx['children']=list(idx['children'].values())
    for idx2 in idx['children']:
        idx2['children']=list(idx2['children'].values())

with open("./result.json",'w') as f:
    json.dump(output,f)
