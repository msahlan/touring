{{ Former::select('logistic', "Logistic")->options( Prefs::getLogistic()->LogisticToSelection('LOGISTIC_CODE','NAME') )->class('input-sm input-white form-control') }}
{{ Former::select('position', "Initial Position")->options( Prefs::getPosition()->PositionToSelection('NODE_CODE','NAME') )->class('input-sm input-white form-control') }}