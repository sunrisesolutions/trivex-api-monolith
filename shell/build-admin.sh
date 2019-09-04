cd ..;
export ROOT=$(pwd);
echo 'PROJECT APP IS ' $ROOT;
export ADMIN=$ROOT'/services/admin'
echo 'ADMIN APP IS '$ADMIN
echo 'copy Util from user'
#cp -p $ROOT/api/src/Security/DecisionMakingInterface.php $ADMIN/src/Security
echo 'clear Admin Doctrine/Subscriber folder'
rm -R -f $ADMIN/src/Doctrine/Subscriber/*
cp -p $ROOT/api/src/Doctrine/Subscriber/* $ADMIN/src/Doctrine/Subscriber/

echo 'copy Entity'
rm -R -f $ADMIN/src/Entity/*
cp -R -p $ROOT/api/src/Entity/* $ADMIN/src/Entity/

echo 'copy Repository'
rm -R -f $ADMIN/src/Repository/*
cp -R -p $ROOT/api/src/Repository/* $ADMIN/src/Repository/

echo 'copy Filter'
rm -R -f $ADMIN/src/Filter/*
cp -R -p $ROOT/api/src/Filter/* $ADMIN/src/Filter/

echo 'copy Util'
rm -R -f $ADMIN/src/Util/*
cp -R -p $ROOT/api/src/Util/* $ADMIN/src/Util/

echo 'copy Command'
rm -R -f $ADMIN/src/Command/*
cp -R -p $ROOT/api/src/Command/* $ADMIN/src/Command/


#cd ~/workspace/magenta/trivex/api/admin/src/Entity/User
#sed -i -- 's/App\\Entity/App\\Entity\\User/g' *
#sed -i -- 's/App\\Util/App\\Util\\User/g' *
#sed -i -- 's/App\\Repository/App\\Repository\\User/g' *




#
#echo 'copy to configuration'
#mkdir -p ~/workspace/magenta/trivex/api/configuration/api/utils
#rm -R -f ~/workspace/magenta/trivex/api/configuration/api/utils/*
#cp -R ~/workspace/magenta/trivex/api/utils/libraries/component/utils/src ~/workspace/magenta/trivex/api/configuration/api/utils/
#cp -R ~/workspace/magenta/trivex/api/utils/config ~/workspace/magenta/trivex/api/configuration/api/utils/
#
#echo 'copy to user'
#sh ~/workspace/magenta/trivex/api/utils/shell/build-user.sh
#cd ~/workspace/magenta/trivex/api
#
#echo 'copy to authorisation'
#sh ~/workspace/magenta/trivex/api/utils/shell/build-auth.sh
#cd ~/workspace/magenta/trivex/api
#
#echo 'copy to event'
#sh ~/workspace/magenta/trivex/api/utils/shell/build-event.sh
#cd ~/workspace/magenta/trivex/api
#
#
#echo 'copy to messaging'
#sh ~/workspace/magenta/trivex/api/utils/shell/build-messaging.sh
#cd ~/workspace/magenta/trivex/api
#
#echo 'copy to person'
#sh ~/workspace/magenta/trivex/api/utils/shell/build-person.sh
#cd ~/workspace/magenta/trivex/api
#
#echo 'copy to organisation'
#sh ~/workspace/magenta/trivex/api/utils/shell/build-organisation.sh
#cd ~/workspace/magenta/trivex/api
#
#
#echo 'fix Subscriber'
#cd ~/workspace/magenta/trivex/api/admin/src/Doctrine/Subscriber
#sed -i -- 's/App\\XXXUtil/App\\Util/g' *
#sed -i -- 's/App\\XXXEntity/App\\Entity/g' *
#cd ~/workspace/magenta/trivex/api
#
#echo 'copy to admin'
#
#echo 'done'
