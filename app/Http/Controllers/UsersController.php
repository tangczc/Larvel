<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;
use App\Models\User;
use Auth;
use Mail;

class UsersController extends Controller
{
    public function __construct(){
        //过滤器
        $this -> middleware('auth',[
            'except' => ['show','signUp','store','index','confirmEmail']
        ]);
        $this -> middleware('guest',[
            'only' => ['create'],
        ]);
    }
    public function signUp(){
        /**
         * 跳转到注册界面
         * return view sign
         */
        return view('users.sign_up');
    }
    /**
     * 通过排序分组把用户分组显示到页面
     * return view
     */
    public function show(User $user){
        //分页排序
        $statuses = $user->statuses() -> orderBy('created_at','desc') -> paginate(30);
        //返回视图
        return view('users.show', compact('user','statuses'));
    }
    /**
    *登陆
    *
    */
    public function store(Request $request){
        //验证规则
        $this->validate($request, [
            //name不能为空大于50
            'name' => 'required|max:50',
            //email不能为空zaiusers上必须唯一格式是email最大长度为255
            'email' => 'required|email|unique:users|max:255',
            //password不能为空最小为6
            'password' => 'required|confirmed|min:6'
        ]);
        //
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
        ]);
        $this->sendEmailConfirmationTo($user);
        session()->flash('success', '验证邮件已发送到你的注册邮箱上，请注意查收。');
        Auth::login($user);
       // session()->flash('success', '欢迎加入我们~');
       // return redirect()->route('users.show', [$user]);
       return redirect('/');
    }
    /**
    *
    * 跳转用户修改页面
    * return view
    * 
    */
    public function edit(User $user){
        $this -> authorize('update',$user);
        return view('users.edit',compact('user'));
    }
    /**
    * 修改用户信息
    */
    public function update(User $user,Request $request){
        $this -> validate($request, [
            'name' => 'required|max:50',
            'password' => 'required|confirmed|min:6',
        ]);
        $this -> authorize('update',$user);
        $data = [];
        $data['name'] = $request -> name;
        if($request -> password){
            $data['password'] =  bcrypt($request -> password);
        }
        $user -> update($data);
        session() -> flash('success','修改成功');
        return redirect() -> route('users.show', $user -> id);
    }
    /**
    * 分页展示所有用户
    * return view
    */
    public function index(){
        $users = User::paginate(10);
        return view('users.index',compact('users'));
    }
    /**
    * 删除用户
    */
    public function destroy(User $user){
        $this -> authorize('destroy',$user);
        $user->delete();
        session() -> flash('success','成功删除用户');
        return back();
    }
    /**
    * 确认邮件
    */
    protected function sendEmailConfirmationTo($user){
        $view = 'emails.confirm';
        $data = compact('user');
        $to = $user->email;
        $subject = "感谢注册 Sample 应用！请确认你的邮箱。";

        Mail::send($view, $data, function ($message) use ($to, $subject) {
            $message->to($to)->subject($subject);
        });
    }
    public function confirmEmail($token)
    {
        $user = User::where('activation_token', $token)->firstOrFail();

        $user->activated = true;
        $user->activation_token = null;
        $user->save();

        Auth::login($user);
        session()->flash('success', '恭喜你，激活成功！');
        return redirect()->route('users.show', [$user]);
    }
    public function followings(User $user){
        $users = $user -> followings() -> paginate(30);
        $title = "关注的人";
        return view('users.show_follow', compact('users', 'title'));
    }
    public function followers(User $user){
        $users = $user -> followings() -> paginate(30);
        $title = '粉丝';
        return view('users.show_follow', compact('users', 'title'));
    }

    public function author(){
        return view('static_pages.author');
    }
}
