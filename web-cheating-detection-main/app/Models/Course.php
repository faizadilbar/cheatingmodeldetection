<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Course extends Model
{
    use HasFactory;
    protected $fillable = ['course_title', 'course_code', 'teacher_id', 'is_active'];

    public function teacher()
    {
        return $this->belongsTo(ExamUser::class, 'teacher_id');
    }

    public function students()
    {
        return $this->belongsToMany(ExamUser::class, 'course_user', 'course_id', 'user_id')
            ->withPivot('assign_count')->withTimestamps();
    }

    public function quizzes()
    {
        return $this->hasMany(Quiz::class);
    }
}
