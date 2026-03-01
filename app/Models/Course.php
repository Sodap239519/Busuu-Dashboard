<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Course extends Model {
    use HasFactory;

    protected $fillable = ['name', 'language', 'level', 'description', 'total_lessons', 'estimated_hours', 'icon', 'color'];

    public function lessons() {
        return $this->hasMany(Lesson::class);
    }

    public function userProgress() {
        return $this->hasMany(UserProgress::class);
    }

    public function learningSessions() {
        return $this->hasMany(LearningSession::class);
    }
}
