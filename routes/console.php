<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('attendance:fill-missing')->dailyAt('23:30')->timezone('Asia/Dhaka');
