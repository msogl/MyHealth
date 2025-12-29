<?php
//===================================================================
// Copyright by MSO Great Lakes, 2010-Present. All rights reserved.
//===================================================================
@unlink(__DIR__.'/templates/sitedown.html');
header('Location: index.php');
