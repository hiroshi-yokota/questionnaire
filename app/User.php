<?php

namespace App;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];
    /**
     * このユーザが所有する投稿。（ Micropostモデルとの関係を定義）
     */
    public function microposts()
    {
        return $this->hasMany(Micropost::class);
    }
    /**
     * このユーザがフォロー中のユーザ。（ Userモデルとの関係を定義）
     */
    public function followings()
    {
        return $this->belongsToMany(User::class, 'user_follow', 'user_id', 'follow_id')->withTimestamps();
    }
    /**
     * このユーザをフォロー中のユーザ。（ Userモデルとの関係を定義）
     */
    public function followers()
    {
        return $this->belongsToMany(User::class, 'user_follow', 'follow_id', 'user_id')->withTimestamps();
    }
    /**
     * このユーザがお気に入り登録している投稿。
     */
    public function favorites()
    {
        return $this->belongsToMany(Micropost::class, 'favorites', 'user_id', 'micropost_id')->withTimestamps();
    }
    /**
     * このユーザに関係するモデルの件数をロードする。
     */
    public function loadRelationshipCounts()
    {
//        $this->loadCount('microposts');
        $this->loadCount(['microposts', 'followings', 'followers','favorites']);

    }
    /**
     * $userIdで指定されたユーザをフォローする。
     *
     * @param  int  $userId
     * @return bool
     */
    public function follow($userId)
    {
        // すでにフォローしているかの確認
        $exist = $this->is_following($userId);
        // 相手が自分自身かどうかの確認
        $its_me = $this->id == $userId;

        if ($exist || $its_me) {
            // すでにフォローしていれば何もしない
            return false;
        } else {
            // 未フォローであればフォローする
            $this->followings()->attach($userId);
            return true;
        }
    }

    /**
     * $userIdで指定されたユーザをアンフォローする。
     *
     * @param  int  $userId
     * @return bool
     */
    public function unfollow($userId)
    {
        // すでにフォローしているかの確認
        $exist = $this->is_following($userId);
        // 相手が自分自身かどうかの確認
        $its_me = $this->id == $userId;

        if ($exist && !$its_me) {
            // すでにフォローしていればフォローを外す
            $this->followings()->detach($userId);
            return true;
        } else {
            // 未フォローであれば何もしない
            return false;
        }
    }
    /**
     * 指定された $userIdのユーザをこのユーザがフォロー中であるか調べる。フォロー中ならtrueを返す。
     *
     * @param  int  $userId
     * @return bool
     */
    public function is_following($userId)
    {
        // フォロー中ユーザの中に $userIdのものが存在するか
        return $this->followings()->where('follow_id', $userId)->exists();
    }

/* *********************** 追加 Start *********************** */

    /**
     * $micropstIdで指定されたユーザをフォローする。
     *
     * @param  int  $micropstId
     * @return bool
     */
    public function favorite($micropstId)
    {
        // すでにお気に入りしているかの確認
        $exist = $this->is_favorites($micropstId);

        if ($exist) {
            // すでにお気に入りしていれば何もしない
            return false;
        } else {
            // 未お気に入りであればお気に入りする
            $this->favorites()->attach($micropstId);
            return true;
        }
    }

    /**
     * $micropstIdで指定されたつぶやきをお気に入り解除する。
     *
     * @param  int  $micropstId
     * @return bool
     */
    public function unfavorite($micropstId)
    {
        // すでにお気に入りしているかの確認
        $exist = $this->is_favorites($micropstId);

        if ($exist) {
            // すでにお気に入りしているときはお気に入り解除する
            $this->favorites()->detach($micropstId);
            return true;
        } else {
            // 未お気に入りであれば何もしない
            return false;
        }
    }
    /**
     * 指定された $micropstIdのつぶやきをこのユーザがお気に入り中であるか調べる。お気に入り中ならtrueを返す。
     *
     * @param  int  $micropstId
     * @return bool
     */
    public function is_favorites($micropstId)
    {
        // お気に入り中のつぶやきの中に $micropstIdのものが存在するか
        return $this->favorites()->where('micropost_id', $micropstId)->exists();
    }

/* *********************** 追加 End *********************** */

    /**
     * このユーザとフォロー中ユーザの投稿に絞り込む。
     */
    public function feed_microposts()
    {
        // このユーザがフォロー中のユーザのidを取得して配列にする
        $userIds = $this->followings()->pluck('users.id')->toArray();
        // このユーザのidもその配列に追加
        $userIds[] = $this->id;
        // それらのユーザが所有する投稿に絞り込む
        return Micropost::whereIn('user_id', $userIds);
    }
}