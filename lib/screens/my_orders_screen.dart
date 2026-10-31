import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../providers/app_state.dart';
import '../services/api_service.dart';

class MyOrdersScreen extends StatefulWidget {
  const MyOrdersScreen({super.key});
  @override State<MyOrdersScreen> createState()=>_MyOrdersScreenState();
}
class _MyOrdersScreenState extends State<MyOrdersScreen> {
  final api=ApiService();
  bool loading=true;
  List<dynamic> orders=[];
  String? error;
  @override void initState(){super.initState();load();}
  Future<void> load()async{
    if(!context.read<AppState>().isLoggedIn){
      setState((){loading=false;error='سجل الدخول لعرض طلباتك';});
      return;
    }
    try {
      final data=await api.getMyOrders(context.read<AppState>().token);
      if(mounted)setState((){orders=data;loading=false;error=null;});
    }catch(_){if(mounted)setState((){loading=false;error='تعذر تحميل الطلبات';});}
  }
  String status(dynamic value)=>switch(value){
    'pending'=>'معلق', 'processing'=>'قيد التجهيز','shipped'=>'تم الشحن',
    'delivered'=>'تم التسليم','cancelled'=>'ملغى',_=>'$value',
  };
  @override Widget build(BuildContext context){
    return Scaffold(appBar:AppBar(title:const Text('طلباتي')),
      body:loading?const Center(child:CircularProgressIndicator()):
        error!=null?Center(child:Text(error!)):
        RefreshIndicator(onRefresh:load,child:ListView.builder(
          physics:const AlwaysScrollableScrollPhysics(),
          itemCount:orders.length,
          itemBuilder:(context,i){
            final order=Map<String,dynamic>.from(orders[i] as Map);
            return Card(margin:const EdgeInsets.symmetric(horizontal:12,vertical:7),
              child:ListTile(
                title:Text('طلب #${order['id']} — ${status(order['status'])}'),
                subtitle:Text('القيمة: ${order['total']} جنيه | ${order['cod_collected_at']==null?'لم يسجل التحصيل':'تم تسجيل التحصيل'}'),
              ));
          },
        )),
    );
  }
}
