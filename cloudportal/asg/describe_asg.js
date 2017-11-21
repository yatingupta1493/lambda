var AWS = require('aws-sdk');

exports.handler = (event, context, callback) => {
    
    context.callbackWaitsForEmptyEventLoop = true;
    // TODO implement
    var number = (event.number === undefined ? 'No-Number' : event.number);
    var region = (event.region === undefined ? 'us-east-1' : event.region);
    var Description = (event.Description === undefined ? 'No-Description' : event.Description);
    var AutoS_Name = (event.AutoS_Name === undefined ? 'No-AutoS_Name' : event.AutoS_Name);
    var accessKey = (event.accessKey === undefined ? 'No-accesskey' : event.accessKey);
    var secretKey = (event.secretKey === undefined ? 'No-secretkey' : event.secretKey);
    if (accessKey === "No-accesskey" || accessKey === null || secretKey === "No-secretkey" || secretKey ===null) {
        var autoscaling = new AWS.AutoScaling({apiVersion: '2017-11-15',region: region});
        console.log("test1");
    } else {
        var autoscaling = new AWS.AutoScaling({apiVersion: '2016-11-15',region: region, accessKeyId: accessKey, secretAccessKey: secretKey});
        console.log("test2");
    }
    
    var params = {
        
	    "AutoScalingGroupNames": [
	        AutoS_Name
	     ]
    };
    
    autoscaling.describeAutoScalingGroups(params, function(err, data){
        if (err) console.log(err, err.stack); // an error occurred
        else {
            callback(null, JSON.stringify(data["AutoScalingGroups"][0]["Instances"]));
        }
    });
    
    
};
