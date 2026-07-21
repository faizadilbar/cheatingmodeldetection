<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Quiz extends Model
{
    use HasFactory;
    protected $fillable = [
        'quiz_code','quiz_name','description','teacher_id','course_id',
        'quiz_date','start_time','end_time','duration','total_questions',
        'total_marks','difficulty','is_poll'
    ];
    protected $casts = ['is_poll' => 'boolean', 'quiz_date' => 'date'];

    public function questions() { return $this->hasMany(Question::class); }
    public function teacher()   { return $this->belongsTo(ExamUser::class, 'teacher_id'); }
    public function course()    { return $this->belongsTo(Course::class); }
    public function attempts()  { return $this->hasMany(QuizAttempt::class); }
    public function results()   { return $this->hasMany(QuizResult::class); }
}
