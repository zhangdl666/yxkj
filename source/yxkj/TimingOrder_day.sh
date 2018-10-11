#生成保养工单
curl http://cloud.youxspace.com/TimingOper/create_upkeep;

#计算逾期时间
curl http://cloud.youxspace.com/TimingOper/Beoverdue;

#认领有效期判断
curl http://cloud.youxspace.com/TimingOper/getCheckTime;

#计算共享模式下设备使用天时
curl http://cloud.youxspace.com/TimingOper/device_oper_num;