var AWS = require('aws-sdk');

exports.handler = (event, context, callback) => {
    
    context.callbackWaitsForEmptyEventLoop = true;
    // TODO implement
    var number = (event.number === undefined ? 'No-Number' : event.number);
    var region = (event.region === undefined ? 'us-east-1' : event.region);
    var Instance_Id = (event.Instance_Id === undefined ? 'No-Instance Id' : event.Instance_Id);
    var Description = (event.Description === undefined ? 'No-Description' : event.Description);
    var Instance_Name = (event.Instance_Name === undefined ? 'No-Instance name' : event.Instance_Name);
    var accessKey = (event.accessKey === undefined ? 'No-accesskey' : event.accessKey);
    var secretKey = (event.secretKey === undefined ? 'No-secretkey' : event.secretKey);
    if (accessKey === "No-accesskey" || accessKey === null || secretKey === "No-secretkey" || secretKey ===null) {
        var ec2 = new AWS.EC2({apiVersion: '2017-11-15',region: region});
        console.log("test1");
    } else {
        var ec2 = new AWS.EC2({apiVersion: '2016-11-15',region: region, accessKeyId: accessKey, secretAccessKey: secretKey});
        console.log("test2");
    }
    
    var params = {
        
	    "Filters": [
	        {
	            Name: "attachment.instance-id",
	            Values: [Instance_Id]
	        }
	     ]
    };
    
    ec2.describeVolumes(params, function(err, data) {
        if (err) console.log(err, err.stack); // an error occurred
        else {     
            console.log(data);
            callback(null, JSON.stringify(data));
        }
    });
    
    
};
