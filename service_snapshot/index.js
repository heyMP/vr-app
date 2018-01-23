const {buffer, text, json} = require('micro')

module.exports = async (req, res) => {
  const buf = await buffer(req)
  console.log(buf);
  res.end('Welcome to Micro')
};