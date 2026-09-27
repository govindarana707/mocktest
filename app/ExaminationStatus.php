<?php

namespace App;

enum ExaminationStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
}
