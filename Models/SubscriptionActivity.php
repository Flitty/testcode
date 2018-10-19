<?php
/**
 * Created by PhpStorm.
 * User: nikolaygolub
 * Date: 04.10.2018
 * Time: 15:18
 */

namespace Subscription\Models;


use Illuminate\Database\Eloquent\Model;

/**
 * Subscription\Models\SubscriptionActivity
 *
 * @mixin \Eloquent
 */
class SubscriptionActivity extends Model
{
    protected $table = 'subscribtion_activities';
    protected $fillable = [
        'type',
        'message',
        'body'
    ];
}