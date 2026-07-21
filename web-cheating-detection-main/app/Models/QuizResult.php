<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class QuizResult extends Model
{
    protected $fillable = [
        'quiz_id','student_id','course_id','course_name','total_questions',
        'correct_answers','wrong_answers','score','percentage','status',
        'submitted_at','is_result_published','short_answers_data'
    ];
    protected $casts = ['short_answers_data'=>'array','submitted_at'=>'datetime'];
    public function quiz()    { return $this->belongsTo(Quiz::class); }
    public function student() { return $this->belongsTo(ExamUser::class,'student_id'); }
}
