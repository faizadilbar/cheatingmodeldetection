<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    protected $fillable = ['quiz_id','type','question','correct_answer','keyword_weight'];
    public function options() { return $this->hasMany(Option::class); }
    public function quiz()    { return $this->belongsTo(Quiz::class); }
}
