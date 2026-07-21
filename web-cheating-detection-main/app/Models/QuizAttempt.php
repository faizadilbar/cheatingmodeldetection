<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class QuizAttempt extends Model
{
    protected $fillable = [
        'quiz_id','student_id','student_name','student_identifier','quiz_code',
        'course_name','status','tab_switch_count','allowed_reentry','loaded_at','last_active_at'
    ];
    protected $casts = ['loaded_at'=>'datetime','last_active_at'=>'datetime','allowed_reentry'=>'boolean'];
    public function quiz()    { return $this->belongsTo(Quiz::class); }
    public function student() { return $this->belongsTo(ExamUser::class,'student_id'); }
}
