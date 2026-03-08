const express = require('express');
const cors = require('cors');
const helmet = require('helmet');
const morgan = require('morgan');
const compression = require('compression');
const path = require('path');

const corsOptions = require('./config/cors');
const routes = require('./routes');
const webRoutes = require('./routes/webRoutes');
const { errorHandler, notFound } = require('./middleware/errorHandler');
const { globalLimiter } = require('./middleware/rateLimiter');
const { requestLogger } = require('./middleware/logger');

const app = express();

app.set('view engine', 'ejs');
app.set('views', path.join(__dirname, 'views'));

app.use(helmet({ contentSecurityPolicy: false }));
app.use(cors(corsOptions));
app.use(compression());
app.use(express.json({ limit: '10mb' }));
app.use(express.urlencoded({ extended: true, limit: '10mb' }));

if (process.env.NODE_ENV === 'development') {
  app.use(morgan('dev'));
}
app.use(requestLogger);

app.use(express.static(path.join(__dirname, '../frontend/public')));
app.use('/api', globalLimiter);
app.use('/api', routes);
app.use('/', webRoutes);

app.use(notFound);
app.use(errorHandler);

module.exports = app;
