const { DataTypes } = require('sequelize');
const { sequelize } = require('../config/database');

const Device = sequelize.define('Device', {
  id: {
    type: DataTypes.UUID,
    defaultValue: DataTypes.UUIDV4,
    primaryKey: true
  },
  deviceId: {
    type: DataTypes.STRING,
    unique: true,
    allowNull: false,
    field: 'device_id'
  },
  userId: {
    type: DataTypes.UUID,
    allowNull: false,
    field: 'user_id',
    references: {
      model: 'users',
      key: 'id'
    }
  },
  deviceName: {
    type: DataTypes.STRING,
    allowNull: false,
    field: 'device_name'
  },
  deviceType: {
    type: DataTypes.ENUM('mobile', 'desktop', 'tablet', 'web'),
    allowNull: false,
    field: 'device_type'
  },
  platform: {
    type: DataTypes.STRING,
    allowNull: true,
    field: 'platform'
  },
  appVersion: {
    type: DataTypes.STRING,
    allowNull: true,
    field: 'app_version'
  },
  lastSyncAt: {
    type: DataTypes.DATE,
    allowNull: true,
    field: 'last_sync_at'
  },
  syncToken: {
    type: DataTypes.STRING,
    unique: true,
    allowNull: false,
    field: 'sync_token'
  },
  isActive: {
    type: DataTypes.BOOLEAN,
    defaultValue: true,
    field: 'is_active'
  },
  lastSeenAt: {
    type: DataTypes.DATE,
    defaultValue: DataTypes.NOW,
    field: 'last_seen_at'
  },
  pushToken: {
    type: DataTypes.STRING,
    allowNull: true,
    field: 'push_token'
  },
  metadata: {
    type: DataTypes.JSONB,
    allowNull: true,
    field: 'metadata'
  }
}, {
  tableName: 'devices',
  timestamps: true,
  indexes: [
    {
      fields: ['user_id', 'is_active']
    },
    {
      fields: ['device_id']
    },
    {
      fields: ['sync_token']
    }
  ]
});

module.exports = Device;