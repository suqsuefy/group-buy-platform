import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../providers/app_state.dart';
import '../services/api_service.dart';

class CheckoutScreen extends StatefulWidget {
 const CheckoutScreen({super.key});
 @override State<CheckoutScreen> createState()=>_CheckoutScreenState();
}
class _CheckoutScreenState extends State<CheckoutScreen> {
 final api=ApiService();
 int? addressId;
 List<dynamic> addresses=[];
 bool saving=false;
 final title=TextEditingController(text:'المنزل');
 final city=TextEditingController();
 final area=TextEditingController();
 final phone=TextEditingController();
 @override void initState(){super.initState();_load();}
 Future<void> _load({int? preferredId})async{
  try{final result=await api.getAddresses(context.read<AppState>().token);
   if(!mounted)return;
   setState((){addresses=result;addressId=result.isEmpty?null:(preferredId??addressId??result.first['id'] as int);});
  }catch(e){if(mounted)ScaffoldMessenger.of(context).showSnackBar(SnackBar(content:Text('$e')));}
 }
 Future<void> _addAddress()async{
  try{
   final result=await api.addAddress(token:context.read<AppState>().token,title:title.text,city:city.text,area:area.text,phone:phone.text);
   if(!mounted)return;
   setState(()=>addressId=result['address']['id'] as int);
   await _load(preferredId: result['address']['id'] as int);
  }catch(e){if(mounted)ScaffoldMessenger.of(context).showSnackBar(SnackBar(content:Text('$e')));}
 }
 Future<void> _order()async{
  final app=context.read<AppState>();
  if(app.cart.length!=1||addressId==null)return;
  setState(()=>saving=true);
  try{
   final id=app.cart.first['campaign_id'] as int;
   await api.createOrder(campaignId:id,addressId:addressId!,token:app.token);
   if(!mounted)return;
   app.clearCart();
   ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content:Text('تم تسجيل الطلب')));
   Navigator.pushNamedAndRemoveUntil(context,'/home',(_)=>false);
  }catch(e){if(mounted)ScaffoldMessenger.of(context).showSnackBar(SnackBar(content:Text('$e')));}
  finally{if(mounted)setState(()=>saving=false);}
 }
 @override void dispose(){title.dispose();city.dispose();area.dispose();phone.dispose();super.dispose();}
 @override Widget build(BuildContext context){
  final app=context.watch<AppState>();
  return Scaffold(appBar:AppBar(title:const Text('إتمام الطلب')),body:ListView(padding:const EdgeInsets.all(16),children:[
   const Text('الطلبات متاحة فقط بعد تأكيد اكتمال الحملة. الدفع عند الاستلام.'),
   const SizedBox(height:12),
   if(addresses.isNotEmpty) DropdownButtonFormField<int>(
    initialValue:addressId,
    items:addresses.map((a)=>DropdownMenuItem<int>(value:a['id'] as int,child:Text('${a['title']} - ${a['area']}'))).toList(),
    onChanged:(v)=>setState(()=>addressId=v)),
   const SizedBox(height:14),
   const Text('إضافة عنوان توصيل'),
   TextField(controller:title,decoration:const InputDecoration(labelText:'اسم العنوان')),
   TextField(controller:city,decoration:const InputDecoration(labelText:'المدينة')),
   TextField(controller:area,decoration:const InputDecoration(labelText:'المنطقة')),
   TextField(controller:phone,decoration:const InputDecoration(labelText:'رقم الهاتف')),
   TextButton(onPressed:_addAddress,child:const Text('حفظ العنوان')),
   Text('الإجمالي المتوقع: ${app.total.toStringAsFixed(2)} جنيه'),
   const SizedBox(height:12),
   ElevatedButton(onPressed:saving||addressId==null||app.cart.length!=1?null:_order,child:Text(saving?'جارٍ الإرسال':'تأكيد الطلب')),
  ]));
 }
}
