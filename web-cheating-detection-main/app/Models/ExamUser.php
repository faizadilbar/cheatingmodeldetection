<?php
namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ExamUser extends Authenticatable
{
    use HasFactory;
    protected $table = 'exam_users';
    protected $fillable = ['name', 'email', 'password', 'role', 'rollno'];
    protected $hidden = ['password', 'remember_token'];

    public function courses()
    {
        return $this->hasMany(Course::class, 'teacher_id');
    }

    public function enrolledCourses()
    {
        return $this->belongsToMany(Course::class, 'course_user', 'user_id', 'course_id')
            ->withPivot('assign_count')->withTimestamps();
    }
}
