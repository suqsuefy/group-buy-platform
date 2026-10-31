import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import '../services/api_endpoint.dart';

class ServerSettingsScreen extends StatefulWidget {
  const ServerSettingsScreen({super.key});
  @override State<ServerSettingsScreen> createState() => _ServerSettingsScreenState();
}
class _ServerSettingsScreenState extends State<ServerSettingsScreen> {
  final input=TextEditingController(text:ApiEndpoint.baseUrl);
  bool busy=false;
  String? feedback;
  @override void dispose(){input.dispose();super.dispose();}
  Future<void> save()async{
    setState(()=>busy=true);
    try{
      final url=input.text.trim();
      if(!ApiEndpoint.isValidStagingUrl(url)){
        throw const FormatException('أدخل رابط HTTPS ينتهي بـ /api');
      }
      // Test a read-only endpoint before changing the active server.
      final client=Dio(BaseOptions(
        baseUrl:url,
        connectTimeout:const Duration(seconds:8),
        receiveTimeout:const Duration(seconds:8),
      ));
      final response=await client.get('/campaigns');
      if(response.statusCode!=200 || response.data is! List){
        throw const FormatException('الخادم لا يعيد قائمة الحملات المتوقعة');
      }
      await ApiEndpoint.save(url);
      if(mounted)setState(()=>feedback='تم حفظ خادم الاختبار. ارجع لتسجيل الدخول.');
    }catch(_){
      if(mounted)setState(()=>feedback='فشل التحقق من رابط الخادم أو الاتصال به.');
    }finally{
      if(mounted)setState(()=>busy=false);
    }
  }
  @override Widget build(BuildContext context)=>Scaffold(
    appBar:AppBar(title:const Text('إعدادات خادم الاختبار')),
    body:ListView(padding:const EdgeInsets.all(20),children:[
      const Text('للتجربة فقط: أدخل رابط خادم Laravel تجريبي آمن يبدأ بـ https وينتهي بـ /api. لا تستخدم خادم الإنتاج.'),
      const SizedBox(height:15),
      TextField(controller:input,keyboardType:TextInputType.url,
        decoration:const InputDecoration(
          labelText:'عنوان API التجريبي',hintText:'https://staging.example.com/api',
          border:OutlineInputBorder())),
      const SizedBox(height:14),
      ElevatedButton(onPressed:busy?null:save,child:Text(busy?'جارٍ اختبار الاتصال':'اختبار الاتصال وحفظ الرابط')),
      if(feedback!=null)Padding(padding:const EdgeInsets.only(top:16),child:Text(feedback!)),
    ]),
  );
}
